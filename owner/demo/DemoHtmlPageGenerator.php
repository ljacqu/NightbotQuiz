<?php
  
class DemoHtmlPageGenerator extends HtmlPageGenerator {
  
  function __construct(int $ownerId, DatabaseHandler $db) {
    parent::__construct($ownerId, $db);
  }
}

