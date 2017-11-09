<?php
namespace Controller;

use Silex\Application;

class DefaultController
{
    public function indexAction(Application $app)
    {
        $referencesRows = array();

        // Reading existing file
        $filename = "references.xls";

        if (file_exists("references.xls")) {
            try {
                $inputFileType = \PHPExcel_IOFactory::identify($filename);
                $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
                $objPHPExcel = $objReader->load($filename);
            } catch (\Exception $e) {
                die('Error loading file "' . pathinfo($filename, PATHINFO_BASENAME) . '": ' . $e->getMessage());
            }

            $sheet = $objPHPExcel->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();

            $referencesRows = array();
            $tableName = null;

            for ($i = 3; $i <= $highestRow; $i++) {
                $rowData = $sheet->rangeToArray('A' . $i . ':' . $highestColumn . $i, null, true, false);

                if ($rowData[0][0] && $tableName != $rowData[0][0]) {
                    $tableName = $rowData[0][0];
                }

                if (empty($rowData[0][1]) || empty($rowData[0][2])) {
                    continue;
                }

                $row['TABLE_NAME'] = $tableName;
                $row['COLUMN_NAME'] = $rowData[0][1];
                $row['COLUMN_TYPE'] = $rowData[0][2];
                $row['IS_NULLABLE'] = $rowData[0][3];
                $row['EDD_TABLE_NAME'] = $rowData[0][4];
                $row['EDD_COLUMN_NAME'] = $rowData[0][5];
                $row['EDD_COLUMN_TYPE'] = $rowData[0][6];
                $row['DESCRIPTION'] = $rowData[0][7];

                $identify = strtolower($row['TABLE_NAME'] . '_' . $row['COLUMN_NAME']);
                $referencesRows["{$identify}"] = $row;
            }
        }

        // Create new file
        $db = $app['nativeDB'];

        $query = "  SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = 'kanban'
                    ORDER BY TABLE_NAME, ORDINAL_POSITION asc";

        $db->query($query);

        $rows = $db->fetchAll();

        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);

        // CSS
        $frontStyle = array(
            'font'  => array(
                'bold'  => true,
                'color' => array('rgb' => '4E3952'),
                'size'  => 11,
                'name'  => 'Calibri'
            ),
            'alignment' => array(
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ),
            'fill' => array(
                'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('rgb' => 'DBA1E5')
            ),
            'borders' => array(
                'allborders' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array('rgb' => '000000')
                )
            )
        );

        $eddStyle = array(
            'font'  => array(
                'bold'  => true,
                'color' => array('rgb' => '1F1830'),
                'size'  => 11,
                'name'  => 'Calibri'
            ),
            'alignment' => array(
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ),
            'fill' => array(
                'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('rgb' => '9170E5')
            ),
            'borders' => array(
                'allborders' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array('rgb' => '000000')
                )
            )
        );

        $descriptionStyle = array(
            'font'  => array(
                'bold'  => true,
                'color' => array('rgb' => '000000'),
                'size'  => 11,
                'name'  => 'Calibri'
            ),
            'alignment' => array(
                'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER
            ),
            'fill' => array(
                'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('rgb' => 'BFAB95')
            )
        );

        $tableNameStyle = array(
            'font'  => array(
                'bold'  => true,
                'color' => array('rgb' => '000000'),
                'size'  => 11,
                'name'  => 'Calibri'
            ),
            'alignment' => array(
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ),
            'fill' => array(
                'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('rgb' => 'BFAB95')
            )
        );

        $borderLeftStyle = array(
            'borders' => array(
                'left' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN
                )
            )
        );

        $newValue = array(
            'font'  => array(
                'color' => array('rgb' => '229626'),
                'size'  => 11,
                'name'  => 'Calibri'
            )
        );

        $updatedValue = array(
            'font'  => array(
                'color' => array('rgb' => 'FF9A1F'),
                'size'  => 11,
                'name'  => 'Calibri'
            )
        );

        $objPHPExcel->getActiveSheet()->getStyle("A1:D1")->applyFromArray($frontStyle);
        $objPHPExcel->getActiveSheet()->getStyle("E1:G1")->applyFromArray($eddStyle);
        $objPHPExcel->getActiveSheet()->getStyle("A2:D2")->applyFromArray($frontStyle);
        $objPHPExcel->getActiveSheet()->getStyle("E2:G2")->applyFromArray($eddStyle);
        $objPHPExcel->getActiveSheet()->getStyle("E2:G2")->applyFromArray($eddStyle);
        $objPHPExcel->getActiveSheet()->getStyle("H1:H2")->applyFromArray($descriptionStyle);

        // HTML

        // row 1
        $objPHPExcel->getActiveSheet()->setCellValue('A1', "FRONT");
        $objPHPExcel->getActiveSheet()->mergeCells('A1:D1');

        $objPHPExcel->getActiveSheet()->setCellValue('E1', "EDD");
        $objPHPExcel->getActiveSheet()->mergeCells('E1:G1');

        $objPHPExcel->getActiveSheet()->setCellValue('H1', "DESCRIPTION");
        $objPHPExcel->getActiveSheet()->mergeCells('H1:H2');

        // row 2
        $objPHPExcel->getActiveSheet()->setCellValue('A2', "TABLE");
        $objPHPExcel->getActiveSheet()->setCellValue('B2', "CHAMP");
        $objPHPExcel->getActiveSheet()->setCellValue('C2', "TYPE");
        $objPHPExcel->getActiveSheet()->setCellValue('D2', "VIDE");
        $objPHPExcel->getActiveSheet()->setCellValue('E2', "TABLE");
        $objPHPExcel->getActiveSheet()->setCellValue('F2', "CHAMP");
        $objPHPExcel->getActiveSheet()->setCellValue('G2', "TYPE");

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(40);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(30);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(40);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(30);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(60);

        $headingMarginIndex = 0;
        $tableHeading = null;

        foreach($rows as $index => $row) {
            $index += 3;

            if ($tableHeading != $row['TABLE_NAME']) {

                // table name margin top
                if ($tableHeading != null) {
                    $headingMarginIndex += 2;
                }

                $tableHeading = $row['TABLE_NAME'];

                $headerIndex = $index + $headingMarginIndex;

                $objPHPExcel->getActiveSheet()->setCellValue("A{$headerIndex}", strtoupper($row['TABLE_NAME']));
                $objPHPExcel->getActiveSheet()->getStyle("A{$headerIndex}:H{$headerIndex}")->applyFromArray($tableNameStyle);

                $headingMarginIndex++;
            }

            $index += $headingMarginIndex;

            $objPHPExcel->getActiveSheet()->setCellValue('B' . $index, $row['COLUMN_NAME'])
                                          ->setCellValue('C' . $index, $row['COLUMN_TYPE'])
                                          ->setCellValue('D' . $index, $row['IS_NULLABLE']);

            $identify = strtolower($tableHeading . '_' . $row['COLUMN_NAME']);

            if (!empty($referencesRows[$identify])) {
                $referenceRow = $referencesRows[$identify];
                $objPHPExcel->getActiveSheet()->setCellValue('E' . $index, $referenceRow['EDD_TABLE_NAME'])
                                              ->setCellValue('F' . $index, $referenceRow['EDD_COLUMN_NAME'])
                                              ->setCellValue('G' . $index, $referenceRow['EDD_COLUMN_TYPE'])
                                              ->setCellValue('H' . $index, $referenceRow['DESCRIPTION']);

                // updated cell style
                if ($row['COLUMN_TYPE'] != $referenceRow['COLUMN_TYPE']) {
                    $objPHPExcel->getActiveSheet()->getStyle('C' . $index)->applyFromArray($updatedValue);
                }

                if ($row['IS_NULLABLE'] != $referenceRow['IS_NULLABLE']) {
                    $objPHPExcel->getActiveSheet()->getStyle('D' . $index)->applyFromArray($updatedValue);
                }
            } else {
                // count $referencesRows to only check if is new file to apply or not newValue style
                if (count($referencesRows) > 0) {
                    $objPHPExcel->getActiveSheet()->getStyle('B' . $index)->applyFromArray($newValue);
                    $objPHPExcel->getActiveSheet()->getStyle('C' . $index)->applyFromArray($newValue);
                    $objPHPExcel->getActiveSheet()->getStyle('D' . $index)->applyFromArray($newValue);
                }
            }
        }
		
        // CSS
        $lastIndex = count($rows) + $headingMarginIndex + 3;

        $objPHPExcel->getActiveSheet()->getStyle("E1:H{$lastIndex}")->applyFromArray($borderLeftStyle);
        $objPHPExcel->getActiveSheet()->getStyle("H1:H{$lastIndex}")->applyFromArray($borderLeftStyle);

        // Save Excel 95 file
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

        // Download
		$downloadFilename = 'mapping' . date('Y-m-d') . 'xls';
		
        header('Content-type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $downloadFilename . '"');
        $objWriter->save('php://output');
    }
}