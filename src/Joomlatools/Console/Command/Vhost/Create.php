<?php
/**
 * @copyright	Copyright (C) 2007 - 2015 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license		Mozilla Public License, version 2.0
 * @link		http://github.com/joomlatools/joomlatools-console for the canonical source repository
 */

namespace Joomlatools\Console\Command\Vhost;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Joomlatools\Console\Command\Site\AbstractSite;
use Joomlatools\Console\Joomla\Util;

class Create extends AbstractSite
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('vhost:create')
            ->setDescription('Creates a new Apache2 virtual host')
            ->addOption(
                'http-port',
                null,
                InputOption::VALUE_REQUIRED,
                'The HTTP port the virtual host should listen to',
                (Util::isJoomlatoolsBox() ? 8080 : 80)
            )
            ->addOption(
                'disable-ssl',
                null,
                InputOption::VALUE_NONE,
                'Disable SSL for this site'
            )
            ->addOption(
                'ssl-crt',
                null,
                InputOption::VALUE_REQUIRED,
                'The full path to the signed cerfificate file',
                '/etc/apache2/ssl/server.crt'
            )
            ->addOption(
                'ssl-key',
                null,
                InputOption::VALUE_REQUIRED,
                'The full path to the private cerfificate file',
                '/etc/apache2/ssl/server.key'
            )
            ->addOption(
                'ssl-port',
                null,
                InputOption::VALUE_REQUIRED,
                'The port on which the server will listen for SSL requests',
                '443'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        if (is_dir('/etc/apache2/sites-available'))
        {
            $site = $input->getArgument('site');
            $port = $input->getOption('http-port');
            $path = $this->getApplication()->getDataDir() . '/';
            $tmp  = '/tmp/vhost.tmp';

            $template     = file_get_contents($path.'/vhost.conf');
            $documentroot = Util::isPlatform($this->target_dir) ? $this->target_dir . '/web/' : $this->target_dir;

            file_put_contents($tmp, sprintf($template, $site, $documentroot, $port));

            if (!$input->getOption('disable-ssl'))
            {
                $ssl_crt  = $input->getOption('ssl-crt');
                $ssl_key  = $input->getOption('ssl-key');
                $ssl_port = $input->getOption('ssl-port');

                if (file_exists($ssl_crt) && file_exists($ssl_key))
                {
                    $template = "\n\n" . file_get_contents($path.'/vhost.ssl.conf');
                    file_put_contents($tmp, sprintf($template, $site, $documentroot, $ssl_port, $ssl_crt, $ssl_key), FILE_APPEND);
                }
                else $output->writeln('<comment>SSL was not enabled for the site. One or more certificate files are missing.</comment>');
            }

            `sudo tee /etc/apache2/sites-available/1-$site.conf < $tmp`;
            `sudo a2ensite 1-$site.conf`;
            `sudo /etc/init.d/apache2 restart > /dev/null 2>&1`;

            @unlink($tmp);
        }
    }
}