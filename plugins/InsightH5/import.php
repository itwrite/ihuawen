<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2017/5/26
 * Time: 15:38
 */

require_once('../../simplewind/Core/Library/Vendor/PHPExcel/PHPExcel.php');
include '../../simplewind/Core/Library/Vendor/PHPExcel/PHPExcel/IOFactory.php';
$fileNO = "GMCCBI_07411";
$file = "D:\\$fileNO.xls";

$objReader = PHPExcel_IOFactory::createReader('Excel5');
try{
    $PHPReader = $objReader->load($file);
}catch(Exception $e){
    print($e->getMessage());
}


if(!isset($PHPReader)) return array("error"=>0,'message'=>'read error!');
$allWorksheets = $PHPReader->getAllSheets();
$i = 0;
foreach($allWorksheets as $objWorksheet){
    $sheetname=$objWorksheet->getTitle();

    $allRow = $objWorksheet->getHighestRow();//how many rows
    $highestColumn = $objWorksheet->getHighestColumn();//how many columns
    $allColumn = PHPExcel_Cell::columnIndexFromString($highestColumn);
    $array[$i]["Title"] = $sheetname;
    $array[$i]["Cols"] = $allColumn;
    $array[$i]["Rows"] = $allRow;
    $arr = array();
    $isMergeCell = array();
    foreach ($objWorksheet->getMergeCells() as $cells) {//merge cells
        foreach (PHPExcel_Cell::extractAllCellReferencesInRange($cells) as $cellReference) {
            $isMergeCell[$cellReference] = true;
        }
    }

    $json_content = "";
    for($currentRow = 1 ;$currentRow<=$allRow;$currentRow++){
        if($currentRow<3){
            continue;
        }
        $row = array();
        $json_row_arr = array();
        for($currentColumn=0;$currentColumn<$allColumn;$currentColumn++){

            $cell =$objWorksheet->getCellByColumnAndRow($currentColumn, $currentRow);
            $afCol = PHPExcel_Cell::stringFromColumnIndex($currentColumn+1);
            $bfCol = PHPExcel_Cell::stringFromColumnIndex($currentColumn-1);
            $col = PHPExcel_Cell::stringFromColumnIndex($currentColumn);
            $address = $col.$currentRow;
            $value = $objWorksheet->getCell($address)->getValue();
            if(substr($value,0,1)=='='){
                return array("error"=>0,'message'=>'can not use the formula!');
                exit;
            }
            if($cell->getDataType()==PHPExcel_Cell_DataType::TYPE_NUMERIC){
                $cellstyleformat=$cell->getParent()->getStyle( $cell->getCoordinate() )->getNumberFormat();
                $formatcode=$cellstyleformat->getFormatCode();
                if (preg_match('/^([$[A-Z]*-[0-9A-F]*])*[hmsdy]/i', $formatcode)) {
                    $value=gmdate("Y-m-d", PHPExcel_Shared_Date::ExcelToPHP($value));
                }else{
                    $value=PHPExcel_Style_NumberFormat::toFormattedString($value,$formatcode);
                }
            }
            $temp = "";
            if(isset($isMergeCell[$col.$currentRow])&&$isMergeCell[$col.$currentRow]&&isset($isMergeCell[$afCol.$currentRow])&&$isMergeCell[$afCol.$currentRow]&&!empty($value)){
                $temp = $value;
            }elseif(isset($isMergeCell[$col.$currentRow])&&$isMergeCell[$col.$currentRow]&&isset($isMergeCell[$col.($currentRow-1)])&&$isMergeCell[$col.($currentRow-1)]&&empty($value)){
                $value=$arr[$currentRow-1][$currentColumn];
            }elseif(isset($isMergeCell[$col.$currentRow])&&$isMergeCell[$col.$currentRow]&&isset($isMergeCell[$bfCol.$currentRow])&&$isMergeCell[$bfCol.$currentRow]&&empty($value)){
                $value=$temp;
            }
            $row[$currentColumn] = $value;

            if($currentColumn==2){
                $json_row_arr[] = -1;
                $json_row_arr[] = -1;
                $json_row_arr[] = $fileNO;
            }
            $json_row_arr[] = $value;
        }
        $arr[$currentRow] = $row;

        $json_content.= implode('&&',$json_row_arr)."\n";
    }
    $array[$i]["Content"] = $arr;
    $i++;
}
file_put_contents("D:\\test.txt",$json_content);
unset($objWorksheet);
unset($PHPReader);
unset($PHPExcel);
//unlink($file);
