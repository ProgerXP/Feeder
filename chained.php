<?php
/*
  Standards-compliant, uniform, Unicode-aware feed generator.
  by Proger_XP | in Public Domain | http://proger.i-forge.net/PHP_Feeder/7sg

  This file provides convenient interface to chain calls to individual Feeder
  methods and classes. It requires the main Feeder library.
*/

include_once dirname(__FILE__).'/feeder.php';

class Feed extends Feeder {
  static function make(array $data = null) {
    $feeder = new self($data);
    return new FeedAccessor($feeder->Channel(), new FeedAccessor($feeder));
  }

  function __construct(array $data = null) {
    parent::__construct();
    $data and $this->SetFromArray($data);
  }

  function SetFromArray(array $data) {
    $this->Clear();
    $this->channel->SetFromArray($data);

    if ($entries = &$data['entries']) {
      foreach ($entries as $entry) {
        $this->feed->entries[] = new FeedEntry($entry);
      }
    }
  }

  function Send($format) {
    return $this->CallOutput($format, 'Output');
  }

  function Build($format) {
    return $this->CallOutput($format, 'Build');
  }

  protected function CallOutput($format, $method) {
    $obj = FeedOut::Factory($format);
    $this->Normalize();
    return $obj->$method($this);
  }

  function Atom()         { return $this->Send(__FUNCTION__); }
  function Rss092()       { return $this->Send(__FUNCTION__); }
  function Rss20()        { return $this->Send(__FUNCTION__); }
}

class FeedAccessor {
  public $methods = array('url' => 'URL', 'Webmaster' => 'WebMaster',
                          'Pubdate' => 'PubDate');

  protected $object, $previous;

  function __construct($object, FeedAccessor $previous = null) {
    $this->object = $object;
    $this->previous = $previous;
  }

  function Object() {
    return $this->object;
  }

  function Previous() {
    return $this->previous;
  }

  function Is($class) {
    return $this->object instanceof $class;
  }

  function Feed() {
    return $this->Up('Feeder');
  }

  function Channel() {
    return $this->Up('FeedChannel');
  }

  function Entry(array $data = null) {
    return $this->Feed()->Add($data);
  }

  function Up($class = null) {
    if (!$class) {
      return $this->previous ? $this->previous : $this;
    } elseif ($this->Is($class)) {
      return $this;
    } elseif ($this->previous) {
      return $this->previous->Up($class);
    } else {
      throw new EFeed("Cannot find [$class] parent.", $this->object);
    }
  }

  function __call($name, $arguments) {
    $name = ucfirst($name);

    if (method_exists($this, $name)) {
      return call_user_func_array(array($this, $name), $arguments);
    } else {
      method_exists($this->object, $name) or $name = strtr($name, $this->methods);

      if (count($arguments) == 1 and is_string($arguments[0])) {
        $object = $this->object->$name();
        if (is_object($object)) {
          $object->SetFromString($arguments[0]);
          return $this;
        }
      }

      return $this->WrapCall($name, $arguments);
    }
  }

  function WrapCall($name, array $arguments) {
    $result = call_user_func_array(array($this->object, $name), $arguments);
    return $this->Wrap($result);
  }

  function Wrap($result) {
    return is_object($result) ? (new self($result, $this)) : $this;
  }

  function ClassAccessor($property, $class, $init) {
    $arguments = array(count($this->object->$property()), new $class);
    $object = $this->WrapCall($property, $arguments);

    if ("$init" === '') {
      return $object;
    } else {
      $object->Object()->SetFromString($init);
      return $this;
    }
  }

  function Author($init = '') {
    return $this->ClassAccessor(__FUNCTION__, 'FeedPerson', $init);
  }
  function Contributor($init = '') {
    return $this->ClassAccessor(__FUNCTION__, 'FeedPerson', $init);
  }

  function WebMaster($init = '') {
    return $this->ClassAccessor(__FUNCTION__, 'FeedPerson', $init);
  }

  function Category($init = '') {
    return $this->ClassAccessor(__FUNCTION__, 'FeedCategory', $init);
  }
}

Feed::make()  ->title()->add('text', 'My Feed')->add('html', 'My <em>Feed</em>')->up()
              ->author()->name('Proger_XP')->email('proger.xp@gmail.com')->up()
              ->description()->add('text', 'Just another PHP Feed')->up()
              ->baseurl('http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['REQUEST_URI']).'/')
    ->entry() ->title()->add('text', 'My first post')->up()
              ->updated(strtotime('22 Jan 2011'))
              ->author()->name('Proger_XP')->email('proger.xp@gmail.com')->up()
              ->content()->add('text', 'Thinking about something to write...')
                         ->add('html', 'Thinking about <em>something</em> to write&hellip;')
    ->entry() ->title()->add('text', 'Another filler')->up()
              ->updated(strtotime('23 May 2012'))
              ->author()->name('Proger_XP')->email('proger.xp@gmail.com')->up()
              ->contributor()->name('Camilo')->url('http://camilomm.deviantart.com')->up()
              ->content()->add('html', 'Why? Because he\'s my friend <img src="smile.png" />')
    ->feed()->atom();
