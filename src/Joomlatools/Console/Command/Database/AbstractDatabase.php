<?php
/**
 * @copyright	Copyright (C) 2007 - 2015 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license		Mozilla Public License, version 2.0
 * @link		http://github.com/joomlatools/joomlatools-console for the canonical source repository
 */

namespace Joomlatools\Console\Command\Database;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Joomlatools\Console\Command\Site\AbstractSite;

abstract class AbstractDatabase extends AbstractSite
{
    protected $target_db;
    protected $target_db_prefix = 'sites_';

    protected $mysql;

    protected function configure()
    {
        parent::configure();

        $this->addOption(
            'mysql-login',
            'L',
            InputOption::VALUE_REQUIRED,
            "MySQL credentials in the form of user:password",
            'root:root'
        )
        ->addOption(
            'mysql-host',
            'H',
            InputOption::VALUE_REQUIRED,
            "MySQL host",
            'localhost'
        )
        ->addOption(
            'mysql-port',
            'P',
            InputOption::VALUE_REQUIRED,
            "MySQL port",
            3306
        )
        ->addOption(
            'mysql-db-prefix',
            null,
            InputOption::VALUE_REQUIRED,
            sprintf("MySQL database name prefix. Defaults to `%s`", $this->target_db_prefix),
            $this->target_db_prefix
        )
        ->addOption(
            'mysql-database',
            'db',
            InputOption::VALUE_REQUIRED,
            "MySQL database name. If set, the --mysql-db-prefix option will be ignored."
        )
        ->addOption(
            'mysql-driver',
            null,
            InputOption::VALUE_REQUIRED,
            "MySQL driver",
            'mysqli'
        )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $db_name = $input->getOption('mysql-database');
        if (empty($db_name))
        {
            $this->target_db_prefix = $input->getOption('mysql-db-prefix');
            $this->target_db        = $this->target_db_prefix.$this->site;
        }
        else
        {
            $this->target_db_prefix = '';
            $this->target_db        = $db_name;
        }
        
        $credentials = explode(':', $input->getOption('mysql-login'), 2);

        $this->mysql = (object) array(
            'user'     => $credentials[0],
            'password' => $credentials[1],
            'host'     => $input->getOption('mysql-host'),
            'port'     => (int) $input->getOption('mysql-port'),
            'driver'   => strtolower($input->getOption('mysql-driver'))
        );

        if (!in_array($this->mysql->driver, array('mysql', 'mysqli'))) {
            throw new \RuntimeException(sprintf('Invalid MySQL driver %s', $this->mysql->driver));
        }

        return 0;
    }

    protected function _backupDatabase($target_file)
    {
        $this->_executeMysqldump(sprintf("--skip-dump-date --skip-extended-insert --no-tablespaces %s > %s", $this->target_db, $target_file));
    }

    protected function _executePDO($query, $database = null) {
        $database = $database ?: $this->target_db;
        $connectionString = "mysql:host={$this->mysql->host}:{$this->mysql->port};dbname={$database};charset=utf8mb4";
        $pdoDB = new \PDO($connectionString, $this->mysql->user, $this->mysql->password);
        $pdoDB->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return $pdoDB->query($query);
    }

    protected function _executeSQL($query, $database = '')
    {
        return $this->_executeMysqlWithCredentials(function($path) use($query, $database) {
            return "echo '$query' | mysql --defaults-extra-file=$path $database";
        });
    }

    protected function _executeMysql($command)
    {
        return $this->_executeMysqlWithCredentials(function($path) use($command) {
            return "mysql --defaults-extra-file=$path $command";
        });
    }
    
    protected function _executeMysqldump($command)
    {
        return $this->_executeMysqlWithCredentials(function($path) use($command) {
            return "mysqldump --defaults-extra-file=$path $command";
        });
    }

    /**
     * Write a temporary --defaults-extra-file file and execute a Mysql command given from the callback
     *
     * @param callable $callback Receives a single string with the path to the --defaults-extra-file path
     * @return void
     */
    private function _executeMysqlWithCredentials(callable $callback)
    {
        try {
            $file = tmpfile();
            $path = stream_get_meta_data($file)['uri'];

            $user = $this->processString($this->mysql->user);
            $password = $this->processString($this->mysql->password);
            $host = $this->processString($this->mysql->host);
            $port = $this->processString($this->mysql->port);

            $contents = <<<STR
[client]
user={$user}
password={$password}
host={$host}
port={$port}
STR;

            fwrite($file, $contents);

            return exec($callback($path));
        }
        finally {
            if (\is_resource($file)) {
                \fclose($file);
            }
        }
    }

    protected function _promptDatabaseDetails(InputInterface $input, OutputInterface $output)
    {
        $this->mysql->user     = $this->_ask($input, $output, 'MySQL user', $this->mysql->user, true);
        $this->mysql->password = $this->_ask($input, $output, 'MySQL password', $this->mysql->password, true, true);
        $this->mysql->host     = $this->_ask($input, $output, 'MySQL host', $this->mysql->host, true);
        $this->mysql->port     = (int) $this->_ask($input, $output, 'MySQL port', $this->mysql->port, true);
        $this->mysql->driver   = $this->_ask($input, $output, 'MySQL driver', array('mysqli', 'mysql'), true);

        $output->writeln('Choose the database name. We will attempt to create it if it does not exist.');
        $this->target_db       = $this->_ask($input, $output, 'MySQL database', $this->target_db, true);

        $input->setOption('mysql-login', $this->mysql->user . ':' . $this->mysql->password);
        $input->setOption('mysql-host', $this->mysql->host);
        $input->setOption('mysql-port', $this->mysql->port);
        $input->setOption('mysql-database', $this->target_db);
        $input->setOption('mysql-driver', $this->mysql->driver);
    }

    private function processString(string $input): string
    {
        // Undo escaped characters
        $input = stripslashes($input);

        // Check if the string starts and ends with single quotes and replace with double quotes
        // or add double quotes if the string does not start and end with quotes
        if (preg_match("/^'(.*)'$/", $input, $matches)) {
            $input = '"' . $matches[1] . '"';
        } elseif (!preg_match('/^["\'].*["\']$/', $input)) {
            $input = '"' . $input . '"';
        }

        return $input;
    }
}
