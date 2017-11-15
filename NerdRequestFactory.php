<?php
class NerdRequestFactory
{
    //put your code here
    const MINIMUM_TEXT_SIZE = 11 ;
      
    
    public function createDisambiguateRequest(IGenericEntity $entity, $endpoint )
    {
       
        try{
             if(strlen($entity->getText() ) < self::MINIMUM_TEXT_SIZE){
            
                throw new NerdRequestFactoryException("text " . $entity->getText() ." is too short");
            }           
            
            $Request =  (new NerdRequest() )->withUri( new Uri( $endpoint ) )
                                            ->withAttribute(INerdAttributes::TEXT, $entity->getText() );
                                            
            
        } 
        catch (EmptyFieldException $ex) {
            throw new NerdRequestFactoryException($ex->getMessage() );
        }
        
        
        
        try{
                        
            $Request = $Request->withAttribute(INerdAttributes::LANGUAGES, (object) array("lang" => $entity->getLanguages()[0] )  )
                                ->withAttribute(INerdAttributes::RESULT_LANGUAGES, $entity->getLanguages() );
            
        } 
        catch (EmptyFieldException $ex) {
        }
        
        return $Request ;     
        
    }
    
    public function createDisambiguateParagraphsRequests(IDocumentEntity $entity, $endpoint)
    {
      
        try{
            $paragraphs = $entity->getParagraphs() ;
        } 
        catch (EmptyFieldException $ex) {
             throw new NerdRequestFactoryException("getting paragraphs failed : " . $ex->getMessage() );
        }
        
        $requests = array() ;
       
        $wholeText = "";
        
        $sentences = array() ;
        
        $i = 0;
        
        foreach($paragraphs as $text){
            
            $offsetStart = strlen( utf8_decode($wholeText) );
            
            $wholeText .= $text ;
            
            if(strlen($text) < self::MINIMUM_TEXT_SIZE){
                 
                 continue ;
            }          
                       
            $sentences[] = (object) array("offsetStart" => $offsetStart,
                                          "offsetEnd" => strlen( utf8_decode($wholeText) )  );
                     
             
            $Request =  (new NerdRequest() )->withUri( new Uri( $endpoint ) )
                                            ->withAttribute(INerdAttributes::SENTENCES, $sentences )
                                            ->withAttribute( INerdAttributes::PROCESS_SENTENCE, array( $i++ ) )
                                            ->withAttribute(INerdAttributes::TEXT, $wholeText);
                                                                                                             
            try{
                                          
                  $Request = $Request->withAttribute(INerdAttributes::LANGUAGES, (object) array("lang" => $entity->getLanguages()[0] )  )
                                     ->withAttribute(INerdAttributes::RESULT_LANGUAGES, $entity->getLanguages() );
            
                } 
                catch (EmptyFieldException $ex) {
                }
              
            $requests[] = $Request ;
            
        }
        
        if(count($requests) == 0){
            throw new NerdRequestFactoryException("no paragraphs detected" );
        }
       
        return $requests ;
    }
}
?>
