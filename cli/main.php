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
        'url' => $input->getArgument('url')
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
            $col->getComment() && $cXml->addAttribute('comment', $col->getComment());
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
    $dom->formatOutput = true;
    echo $dom->saveXML();
}
