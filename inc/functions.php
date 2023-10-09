<?php

function toResultJson($text) {
  return json_encode(['result' => $text], JSON_FORCE_OBJECT);
}

// From https://stackoverflow.com/a/4167053
// For some reason, certain users (maybe using Twitch extensions?) write stuff like
// "xho 󠀀", which has a zero-width space at the end. PHP's trim() does not remove it.
function unicodeTrim($text) {
  return preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $text);
}

function setJsonHeader() {
  header('Content-type: application/json; charset=utf-8');
}
