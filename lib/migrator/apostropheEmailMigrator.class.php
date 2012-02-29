<?php

class apostropheEmailMigrator
{
  public function migrate($sql)
  {
    if (!$sql->columnExists('sf_guard_user', 'email_guid'))
    {
      $sql->sql(array('ALTER TABLE sf_guard_user ADD COLUMN email_guid VARCHAR(20)', 'ALTER TABLE sf_guard_user ADD INDEX email_guid_index(sf_guard_user)'));
      $nullUsers = $sql->query('SELECT * FROM sf_guard_user WHERE email_guid IS NULL');
      foreach ($nullUsers as $nullUser)
      {
      	$nullUser['email_guid'] = aGuid::generate();
      	$sql->query('UPDATE sf_guard_user SET email_guid = :email_guid WHERE id = :id', $nullUser);
      }
      return true;
    }
  }
}

