<?php

function call_excel ($file){
  $targetPath = $file;
  $Reader = new SpreadsheetReader($targetPath);

  $sheetCount = count($Reader->sheets());

  //Preskoci prvi red
  $prolaz = 0;
  $m=0;
  $counter=0;
  $stampaj="";
  echo '<table class="pure-table">';

  for($i=0;$i<$sheetCount;$i++)
  {
      $Reader->ChangeSheet($i);

      foreach ($Reader as $Row){
        $prolaz=0;
        $max_col=count($Row);
        if (!empty($Row[$prolaz])){
            
        for($n=0;$n<$max_col;$n++){
            
          if($m == 0){
            echo '
              <thead>
                <tr>
                  <td colspan="'.$max_col.'">'.$Row[$prolaz].'</td>
                </tr>
              </thead>
              <tbody>
            ';
            $m++;
            $prolaz++;
          }else{
            if($prolaz == 0){
              echo '
                <tr '.$stampaj.'>
                  <td>'.$Row[$prolaz].'</td>';
                  $prolaz++;
            }else{
              if (!empty($Row[$prolaz])){
                echo '<td>'.$Row[$prolaz].'</td>';
              }
                $prolaz++;
            }
          }
        }
      }
        echo '</tr>';

        $counter++;
        if($counter % 2){
          $stampaj = ' class="pure-table-odd"';
        }else {
          $stampaj="";
        }
      }
   }
  echo "
    </tbody>
  </table>
  ";
}
?>
