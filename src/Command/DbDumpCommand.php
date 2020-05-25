<?php

// src/Command/CreateUserCommand.php

namespace App\Command;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use SimpleXMLElement;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DbDumpCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('db:dump')
            ->setDescription('Generate an XML file containing the database schema of the provided database')
            ->addArgument('pdoUrl', InputArgument::REQUIRED, 'Pdo database url')
            ->addOption('tableName', null, InputOption::VALUE_OPTIONAL, 'Export perticual table');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pdoUrl = $input->getArgument('pdoUrl');
        $tableName = $input->getOption('tableName');

        $connectionParams = [
            'url' => $pdoUrl,
        ];

        $config = new Configuration();

        $conn = DriverManager::getConnection($connectionParams, $config);
        $conn->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        $sm = $conn->getSchemaManager();

        $xml = new SimpleXMLElement('<schema/>');

        foreach ($sm->listTables() as $table) {
            if ($tableName && $tableName !== $table->getName()) {
                continue;
            }
            $tXml = $xml->addChild('table');
            $tXml->addAttribute('name', $table->getName());
            foreach ($table->getColumns() as $col) {
                $cXml = $tXml->addChild('column');
                $type = $col->getType()->getSQLDeclaration(['length' => $col->getLength()], $conn->getDatabasePlatform());
                $cXml->addAttribute('name', $col->getName());
                $cXml->addAttribute('type', $col->getType()->getName());
                $col->getLength() && $cXml->addAttribute('length', $col->getLength());
                $col->getAutoincrement() && $cXml->addAttribute('autoincrement', 'true');
                $col->getFixed() && $cXml->addAttribute('fixed', 'true');
                10 != $col->getPrecision() && $cXml->addAttribute('precision', $col->getPrecision());
                $col->getScale() && $cXml->addAttribute('scale', $col->getScale());
                !is_null($col->getDefault()) && $cXml->addAttribute('default', $col->getDefault());
                !$col->getNotnull() && $cXml->addAttribute('notnull', 'false');
                $col->getUnsigned() && $cXml->addAttribute('unsigned', 'true');
                $col->hasPlatformOption('collation') && $cXml->addAttribute('collation', $col->getPlatformOption('collation'));
                $col->getComment() && $cXml->addAttribute('comment', $col->getComment());
            }

            foreach ($table->getIndexes() as $index) {
                $iXml = $tXml->addChild('index');
                $iXml->addAttribute('name', $index->getName());
                foreach (['primary', 'unique'] as $type) {
                    if ($index->{'is'.$type}()) {
                        $iXml->addAttribute(strtolower($type), 'true');
                    }
                }
                $iXml->addAttribute('columns', implode($index->getColumns(), ','));
            }
        }

        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->encoding = 'utf-8';
        $dom->formatOutput = true;
        echo $dom->saveXML();

        return 0;
    }
}
