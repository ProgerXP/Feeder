<?php
require_once 'feeder.php';

$channel = array(
  'title'           => 'My Feed',
  'author'          => 'Proger_XP proger.xp@gmail.com http://proger.i-forge.net',
  'description'     => 'Just another PHP Feed',
  'baseurl'         => 'http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['REQUEST_URI']).'/',
);

$entries = array(
  array(
    'title'         => 'My first post',
    'updated'       => strtotime('22 Jan 2011'),
    'author'        => 'Proger_XP proger.xp@gmail.com',
    'content'       => 'Thinking about something to write...',
    'content html'  => 'Thinking about <em>something</em> to write&hellip;',
  ),
  array(
    'title'         => 'Another filler',
    'updated'       => strtotime('23 May 2012'),
    'author'        => 'Proger_XP proger.xp@gmail.com',
    'contributor'   => 'Camilo http://camilomm.deviantart.com',
    'content html'  => 'Why? Because he\'s my friend <img src="smile.png" />',
  ),
);

$feeder = new Feeder;
$feeder->Channel()->SetFromArray($channel);

foreach ($entries as $i => $entry) {
  $feeder->Entry($i, new FeedEntry($entry));
}

$output = new AtomFeed;
$output->Output($feeder);
