<?php


/*
 * Define the links of overviews that should be included in the page here.
 */
$owners = [
  'medcam' => [
    'link' => 'medcam.php',
    'text' => 'Medcamgaming (geography)',
    'bgcolor' => '#6ab'
  ],
  'highway' => [
    'link' => 'langs.php',
    'text' => 'Highwayscenes (languages)',
    'bgcolor' => '#c86'
  ]
];

$title = 'Quiz overview';
$preface = '<h1>Quiz</h1>
This page runs quizzes for Nightbot. Click on a button below to see the overview of a quiz.';


$appendix = '<p>';
foreach ($owners as $name => $owner) {
  $link = $owner['link'] ?? "$name.php";
  $text = $owner['text'] ?? ucfirst($name);
  $style = !empty($owner['bgcolor']) ? "style='background-color: {$owner['bgcolor']}'" : "";

  $appendix .= "<br /><a href='$link' class='owner' $style>$text</a>";
}
$appendix .= '</p>';

if ($_SERVER['HTTP_HOST'] === 'localhost') {
  $appendix .= '<script src="./indexpage/favicon_remover.js"></script>';
}


$template = file_get_contents('./indexpage/template.html');
echo str_replace(
  ['{title}', '{preface}', '{questions}', '{appendix}'],
  [ $title,     $preface,    '',           $appendix ],
  $template);
