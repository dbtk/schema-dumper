<?php

/**
 *  @Cli("db:dump")
 *  @Arg("url")
 */
function dump($input, $output)
{
    $config = new \Doctrine\DBAL\Configuration();
    $connectionParams = array(
        'url' => $input->getArgument('url')
    );

    $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
    $conn->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

    $sm = $conn->getSchemaManager();

    $xml = new SimpleXMLElement("<schema/>");

    foreach ($sm->listTables() as $table) {
        $tXml = $xml->addChild('table');
        $tXml->addAttribute("name", $table->getName());
        foreach ($table->getColumns() as $col) {
            $cXml = $tXml->addChild('column');
            $type = $col->getType()->getSQLDeclaration(['length' => $col->getLength()], $conn->getDatabasePlatform());
            $cXml->addAttribute('name', $col->getName());
            $cXml->addAttribute('type', $type);
        }

        foreach ($table->getIndexes() as $index) {
            $iXml = $tXml->addChild('index');
            $iXml->addAttribute('name', $index->getName());
            foreach (array('primary', 'unique') as $type) {
                if ($index->{'is' . $type}()) {
                    $iXml->addAttribute($type, $type);
                }
            }
            foreach ($index->getColumns() as $col) {
                $cXml = $iXml->addChild('column');
                $cXml->addAttribute('name', $col);
            }
        }
    }

    $dom = dom_import_simplexml($xml)->ownerDocument;
    $dom->formatOutput = TRUE;
    echo $dom->saveXML();
}
