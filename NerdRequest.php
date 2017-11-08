class NerdRequest extends Request 
{
    
    private $boundary ;
    
    public function __construct()
    {
             
        $this->attributes[INerdAttributes::LANGUAGES] = (object) array("lang" => "en" );
        //$this->attributes[INerdAttributes::SHORT_TEXT] = "";        
       // $this->attributes[INerdAttributes::TERM_VECTOR] = array();
        $this->attributes[INerdAttributes::SENTENCES] = array();
        $this->attributes[INerdAttributes::RESULT_LANGUAGES] = array("en");
        $this->attributes[INerdAttributes::NBEST] = false;
        $this->attributes[INerdAttributes::ONLY_NER] = false;
        $this->attributes[INerdAttributes::CUSTOMISATION] = "generic" ;
        $this->attributes[INerdAttributes::ENTITIES] = array();
        $this->boundary = "---------------------" . md5(mt_rand() . microtime());
        $this->method = "post";
        $this->headers = array("Content-Type" => "multipart/form-data; boundary={$this->boundary}") ;
        
    }
    
     
    public function getBody()
    {
       
        $json = json_encode((object) $this->getAttributes() );
        
        return new MultipartStream(array("query" => $json), $this->boundary ); 
    }
    
    
    
}
