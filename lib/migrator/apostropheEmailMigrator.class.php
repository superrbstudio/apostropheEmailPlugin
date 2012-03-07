<?php

class apostropheEmailMigrator
{
  public function migrate($sql)
  {
    if (!$sql->columnExists('sf_guard_user', 'email_guid'))
    {
      $sql->sql(array('ALTER TABLE sf_guard_user ADD COLUMN email_guid VARCHAR(40)', 'ALTER TABLE sf_guard_user ADD INDEX email_guid_index(sf_guard_user)'));
      // We don't have to initialize email_guids for each user now, they get initialized as needed when getUploadEmailAddress() is called on the user
      return true;
    }
    $data = $sql->query("SHOW CREATE TABLE sf_guard_user");
    if (isset($data[0]['Create Table']))
    {
      $create = $data[0]['Create Table'];
      if (strstr($create, '`email_guid` varchar(20) DEFAULT NULL,') !== false)
      {
        $sql->query('ALTER TABLE sf_guard_user MODIFY COLUMN email_guid varchar(40) DEFAULT NULL');
        return true;
      }
    }
  }
}

