<?php

class apostropheEmailPluginConfiguration extends sfPluginConfiguration
{
  static $registered = false;
  /**
   * @see sfPluginConfiguration
   */
  public function initialize()
  {
    // Yes, this can get called twice. This is Fabien's workaround:
    // http://trac.symfony-project.org/ticket/8026
    
    if (!self::$registered)
    {
      $this->dispatcher->connect('a.migrateSchemaAdditions', array($this, 'migrateListener'));

      self::$registered = true;
    }

    // Adds a preInsert listener that ensures every user gets an email guid
    $table = Doctrine::getTable('sfGuardUser');
    $table->addListener(new apostropheEmailGuidListener());
  }
  
  public function migrateListener($event)
  {
    $migrate = $event->getSubject();
    $migrator = new apostropheEmailMigrator();
    $migrator->migrate($migrate);
  }
}
