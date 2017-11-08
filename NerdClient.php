<?php


class NerdClient
{
    protected $repository;
    protected $configuration ;
    protected $logger ;
      
    public function __construct(NerdConfiguration $configuration, NerdDisambiguationService $repository, Log $logger)
    {
        
        $this->configuration = $configuration ;
        $this->repository = $repository ;
        $this->logger = $logger ;
        
    }
        
    public function request(NerdRequest $Request)
    {
  
        $Response = (new HttpClient() )->request( $Request,
                                                 new Response()
                                                );
        
        if( $Response->getStatusCode() != StatusCode::OK ){
           
            throw new UnexpectedResponseException( $Response->getBody(),
                                                   $Response->getStatusCode()
                                                );
        }
        
        
        return $Response ;
    }
    
    
    public function getDisambiguationRequests(IDocumentEntity $entity)
    {
        try{
             $requests = (new NerdRequestFactory() )->createDisambiguateParagraphsRequests($entity, $this->configuration->getEndpoint() ) ;  // dividing in paragraphs
        } 
        catch (NerdRequestFactoryException $ex) {
            
            $this->logger->debug(" no paragraphs, taking full text " );
            
            try{
                $requests = array( (new NerdRequestFactory() )->createDisambiguateRequest($entity, $this->configuration->getEndpoint()  ) ) ;   // taking whole text
                
            } 
            catch (NerdRequestFactoryException $ex) {
                
                throw new \RuntimeException(" untreatable entity " .$entity->getTitle() . $ex->getMessage() );
                
            }
            
        }

        return $requests ;
    }
    
    public function disambiguate(NerdDisambiguation $disambiguation, IDocumentEntity $document, IPresenter $presenter )
    {
        
        $requests = $this->getDisambiguationRequests($document);
        
        try{
            
            $disambiguation->__set( IDbField::LANGUAGES, json_encode( (object) $document->getLanguages() ) );
            
        } catch (EmptyFieldException $ex) {
            // when no language defined in document
        }
              
        if( $disambiguation->__get(IDbField::REQUESTS_TOTAL) != count($requests) ){
            
            $disambiguation->__set(IDbField::REQUESTS_TOTAL , count($requests) );
        
            $this->repository->update( $disambiguation ) ;
        }
        
        $currentEntities = $disambiguation->getEntities() ;
        
        $offset = $disambiguation->__get(IDbField::REQUESTS_DONE) ; // if previous disambiguation hasn't completed
                        
        foreach(array_slice($requests, $offset) as $key => $request){
            
            try{
                   
                    $this->logger->debug("request N " . $offset + $key . " of " . count($requests) . " -- " . $disambiguation->__get(IDbField::URL) );
                                                                                                           
                    $Response = $this->request( $request->withAttribute( INerdAttributes::ENTITIES, $currentEntities ) );
                                      
                    $currentEntities = (new NerdInputJsonAdapter( $Response->getBody()->getContents() ) )->getEntities() ;
                                       
                    $this->logger->debug("current entities: " . count( $currentEntities ) );
                                                                  
                    $disambiguation->__set( IDbField::DATA, \json_encode( $currentEntities ) ); 
                                                 
                    $disambiguation->__set(IDbField::REQUESTS_DONE , $offset + $key + 1 );   
                                 
                    $this->repository->update( $disambiguation );
                        
                    $presenter->render( $disambiguation ) ;
                                                           
                } 
                catch (UnexpectedResponseException $ex) { //  pb access Nerd Service or getting entities from response
                    
                    $this->logger->debug( $ex->getMessage() ) ;
                  
                }
                catch(InvalidDataException $ex){
                    
                }
                             
        }
             
        $presenter->render( $disambiguation ) ;

        $disambiguation->__set(IDbField::FLAG , NerdDisambiguation::FLAG_DISAMBIGUATION_COMPLETED );
                 
        $this->repository->update( $disambiguation );
        
        return $disambiguation ;
    }
    
       
}
?>
