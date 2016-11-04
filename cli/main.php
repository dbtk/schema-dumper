<?php
/**
 *  @Cli("db:dump")
 *  @Arg("url")
 *  @Option("table", VALUE_OPTIONAL)
 */
function dump($input, $output)
{
    $config = new \Doctrine\DBAL\Configuration();
    $connectionParams = array(
        'url' => $input->getArgument('url'),
    );

    $tableName = $input->getOption('table');

    $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
    $conn->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

    $sm = $conn->getSchemaManager();

    $xml = new SimpleXMLElement("<schema/>");

    foreach ($sm->listTables() as $table) {

        if ($tableName && $tableName !== $table->getName()) {
            continue;
        }
        $tXml = $xml->addChild('table');
        $tXml->addAttribute("name", $table->getName());
        foreach ($table->getColumns() as $col) {
            $cXml = $tXml->addChild('column');
            $type = $col->getType()->getSQLDeclaration(['length' => $col->getLength()], $conn->getDatabasePlatform());
            $cXml->addAttribute('name', $col->getName());
            $cXml->addAttribute('type', $col->getType()->getName());
            $col->getLength() && $cXml->addAttribute('length', $col->getLength());
            $col->getAutoincrement() && $cXml->addAttribute('autoincrement', 'true');
            $col->getFixed() && $cXml->addAttribute('fixed', 'true');
            $col->getPrecision() != 10 && $cXml->addAttribute('precision', $col->getPrecision());
            $col->getScale() && $cXml->addAttribute('scale', $col->getScale());
            !is_null($col->getDefault()) && $cXml->addAttribute('default', $col->getDefault());
            !$col->getNotnull() && $cXml->addAttribute('notnull', 'false');
            $col->getUnsigned() && $cXml->addAttribute('unsigned', 'true');
            $col->getComment() && $cXml->addAttribute('comment', $col->getComment());
        }

        foreach ($table->getIndexes() as $index) {
            $iXml = $tXml->addChild('index');
            $iXml->addAttribute('name', $index->getName());
            foreach (array('primary', 'unique') as $type) {
                if ($index->{'is' . $type}()) {
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
}
