<?php

class HighwayHtmlPageGenerator extends HtmlPageGenerator {

  function __construct(int $ownerId, DatabaseHandler $db) {
    parent::__construct($ownerId, $db);
  }
}
