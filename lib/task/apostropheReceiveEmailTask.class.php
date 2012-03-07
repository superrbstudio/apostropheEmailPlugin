<?php

class apostropheReceiveEmailTask extends sfBaseTask
{
  protected function configure()
  {
    // add your own arguments here
    $this->addArguments(array(
      new sfCommandArgument('filename', sfCommandArgument::REQUIRED, 'Email message filename'),
    ));

    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'frontend'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'doctrine'),
      // add your own options here
    ));

    $this->namespace        = 'apostrophe';
    $this->name             = 'receive-email';
    $this->briefDescription = 'Accept content via email';
    $this->detailedDescription = <<<EOF
The [apostrophe:receive-email|INFO] task accepts content submissions via email.
A file containing the entire email message must be presented as the sole
argument.

The "To:" or "X-Original-To:" header must be of this form:

SHORTNAME+GUID@DOMAIN

Such addresses are shown to users in the media library, etc. to help them submit 
content via email. 

GUID is a unique string automatically generated for each user of the site. 
SHORTNAME can be any string of letters, digits and dashes, and the same goes
for domain; we're really looking for the GUID here. The extra "dressing" is 
accepted to make it easy to give users email addresses they will recognize
later when they want to send more files.

All emails not containing a valid GUID for an existing user are silently 
ignored to avoid backscatter spam problems if an address is accidentally leaked.

Currently all images emailed to this task with a valid address as above are
imported to the media library, and an email response to the user is sent
confirming receipt. Later there will be support for posting blog posts and
perhaps other things.

Hint: an easy way to hook this up is to configure Postfix with a luser_relay
setting that points to a Unix account on the server that can run this task.

Call it with:

  [php symfony apostrophe:receive-email filename|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    // Don't load the configuration until we know what site this is ostensibly for

    // See the sample code at the bottom of http://www.php.net/manual/en/ref.mailparse.php
    // for the best available documentation of the mailparse extension used here.

    $filename = $arguments['filename'];

    // TODO: this could be a longer list from the media library but we don't want to wind up
    // importing every message body as a text file. Think about it. The main audience is
    // only looking to send these types anyway: iOS users who can't upload files normally
    $acceptable = array_flip(array('image/gif', 'image/png', 'image/jpeg'));
    $mime = mailparse_msg_parse_file($filename); 
    $partNames = mailparse_msg_get_structure($mime); 
    $first = true;
    foreach($partNames as $partName) 
    {
      /* get a handle to the message resource for a subsection */ 
      $section = mailparse_msg_get_part($mime, $partName); 
      /* get content-type, encoding and header information for that section */ 
      $info = mailparse_msg_get_part_data($section); 
      if ($first)
      {
        $headers = $info['headers'];
        $to = $headers['to'];
        $subject = isset($headers['subject']) ? $headers['subject'] : '';
        if (!preg_match('/([A-Za-z0-9\-]+)\+(\w+)@/', $to, $matches))
        {
          // Silently ignore spam and other irrelevancies
          return;
        }
        $host = $matches[1];
        $guid = $matches[2];
        // Use a site-specific configuration if available
        if (method_exists('ProjectConfiguration', 'getSiteSpecificConfiguration'))
        {
          $configuration = ProjectConfiguration::getSiteSpecificConfiguration(null, $host);
        }
        else
        {
          $configuration = $this->configuration;
        }
        // So we can play with app.yml settings from the application
        $context = sfContext::createInstance($configuration);
        // initialize the database connection
        $databaseManager = new sfDatabaseManager($configuration);
        $connection = $databaseManager->getDatabase($options['connection'])->getConnection();
        $user = Doctrine::getTable('sfGuardUser')->findOneByEmailGuid($guid);
        if (!$user)
        {
          // No user has this email guid. Silently ignore bad emails as if someone spams
          // a site we would otherwise be sending zillions of replies 
          return;
        }
        // Fake this so the sfCacheSessionStorage class doesn't spew warnings on signin
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        sfContext::getInstance()->getUser()->signin($user, false);
        $first = false;
      }
      $contentType = $info['content-type'];
      if (isset($acceptable[$contentType]))
      {
        $sec = mailparse_msg_get_part($mime, $partName); 
        ob_start();
        mailparse_msg_extract_part_file($sec, $filename);
        $contents = ob_get_clean();
        $table = Doctrine::getTable('aMediaItem');
        $file = aFiles::getTemporaryFilename();
        file_put_contents($file, $contents);
        $options = array();
        if (strlen($subject))
        {
          $options['title'] = $subject;
        }
        $result = $table->addFileAsMediaItem($file, $options);
        unlink($file);
      }
    } 
  }
}

