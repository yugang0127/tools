<?php
/**适用于PHP7.0以上版本*/
header('content-type:text/html;charset=utf-8');
define('DB_HOST','rm-2ze0q80w59h4uyvx4rw.mysql.rds.aliyuncs.com');
define('DB_USER','xin');
define('DB_PASS','48sdf37EB7');
define('DB_NAME','xin_finance');
define('DB_PORT',3306);
define('DB_CHAR','utf8');
define('APPNAME','数据字典');
$conn=mysqli_connect(DB_HOST.':'.DB_PORT,DB_USER,DB_PASS);
mysqli_select_db($conn,DB_NAME);
mysqli_set_charset($conn,DB_CHAR);
$sql="SHOW TABLE STATUS FROM " . DB_NAME;
$result=mysqli_query($conn,$sql);
$array=array();
$tables = ['car_loan_order', 'car_loan_order_more', 'car_half_order_fee_detail', 'car_loan_order_factor'];
while($rows=mysqli_fetch_assoc($result)){
    if (!in_array($rows['Name'], $tables)) {
        continue;
    }
    $array[]=$rows;
}
// table count
$tab_count = count($array);
$dict = "";
$dict = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="zh">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>'.APPNAME.'--数据字典</title>
<style type="text/css">
    table caption, table th, table td {
        padding: 0.1em 0.5em 0.1em 0.5em;
        margin: 0.1em;
        vertical-align: top;
    }
    th {
        font-weight: bold;
        color: black;
        background: #D3DCE3;
    }
    table tr.odd th, .odd {
        background: #E5E5E5;
    }
    table tr.even th, .even {
        background: #f3f3f3;
    }
    .db_table{
        border-top:1px solid #333;
    }
    .title{font-weight:bold;}
</style>
</head>
<body>
<div style="text-align:center;background:#D3DCE3;font-size:19px;">
    <b>'.APPNAME.'--数据字典</b>
</div>
<div style="background:#f3f3f3;text-align:center;">（注：共'.$tab_count.'张表，按ctrl+F查找关键字）</div>'."\n";
for($i=0;$i<$tab_count;$i++){
    $dict .= '<ul type="square">'."\n";
    $dict .= '  <li class="title">';
    $dict .= ($i+1).'、表名：[' . $array[$i]['Name'] . ']      注释：' . $array[$i]['Comment'];
    $dict .= '</li>'."\n";
//查询数据库字段信息
    $tab_name = $array[$i]['Name'];
    $sql_tab='show full fields from `' . $array[$i]['Name'].'`';
    $tab_result=mysqli_query($conn,$sql_tab);
    $tab_array=array();

    while($r=mysqli_fetch_assoc($tab_result)){
        $tab_array[]=$r;
    }
//show keys
    $keys_result=mysqli_query($conn,"show keys from `".$array[$i]['Name'].'`');
    $arr_keys=mysqli_fetch_array($keys_result);
    $dict .= '<li style="list-style: none outside none;"><table border="0" class="db_table" >';
    $dict .= '<tr class="head">
        <th style="width:110px">字段</th>
        <th>类型</th>
        <th>为空</th>
        <th>额外</th>
        <th>默认</th>
        <th style="width:95px">整理</th>
        <th>备注</th></tr>';
    for($j=0;$j<count($tab_array);$j++){
        $key_name=$arr_keys['Key_name'];
        if($key_name="PRIMARY"){
            $key_name='主键（'.$key_name.'）';
        }
        $key_field=$arr_keys['Column_name'];
        if ( $tab_array[$j]['Field']==$key_field){
            $key_value="PK";
        }else{
            $key_value="";
        }
        $dict .= '        <tr class="'.($j%2==0?"odd":"even").'">'."\n";
        $dict .= '          <td>' . $tab_array[$j]['Field'] . '</td>'."\n";
        $dict .= '          <td>' . $tab_array[$j]['Type'] . '</td>'."\n";
        $dict .= '          <td>' . ($key_value!=''?$key_value:$tab_array[$j]['Null']) . '</td>'."\n";
        $dict .= '          <td>' . $tab_array[$j]['Extra'] . '</td>'."\n";
        $dict .= '          <td>' . $tab_array[$j]['Default'] . '</td>'."\n";
        $dict .= '          <td>' . $tab_array[$j]['Collation'] . '</td>'."\n";
        $dict .= '          <td>' . ($key_value!=''?$key_name:$tab_array[$j]['Comment']) . '</td>'."\n";
        $dict .= '        </tr>'."\n";
    }
    $dict .= '  </table></li>'."\n";
    $dict .= '</ul>'."\n";

}
$dict .= '</body>'."\n";
$dict .= '</html>'."\n";
echo $dict;


