<?php
class DB
{
   private static $instance = null;
   public static function get()
   {
       if(self::$instance == null)
       {
           try
           {
               self::$instance = new PDO('mysql:host=localhost;dbname=romeofox_carwash','romeofox_admin','H@rd2crack',
                                          array(PDO::ATTR_PERSISTENT => true));
           } 
           catch(PDOException $e)
           {
               // Handle this properly
               throw $e;
           }
       }
       return self::$instance;
   }
}
?>