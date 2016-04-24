<?php

class PageLeft {
  /**
   * 构造函数
   * @access public
   * @param array $totalRows  总的记录数
   * @param array $listRows   每页显示记录数
   * @param array $parameter  分页跳转的参数
   */
  public function __construct($totalRows, $listRows=20, $url='') {
    $totalRows = (int)$totalRows;   if($totalRows<0){ $totalRows = 0; }
    $listRows  = (int)$listRows;    if($listRows<=0){ $listRows = 20; }

    $totalPages = (int)ceil($totalRows/$listRows);     //总页数
    $varPage    = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
    $nowPage    = !empty($_GET[$varPage])? (int)$_GET[$varPage] : 1;
    if($nowPage<1){
      $nowPage = 1;
    }else if($nowPage>$totalPages){
      $nowPage = $totalPages;
    }

    $a = ($nowPage-1)*$listRows+1;
    $b = $nowPage*$listRows;
    $this->HTML = "显示第 <B>$a</B>-<B>$b</B> 条记录, 共 $totalRows 条记录";
    if($totalPages<=1) return;//如果只有一页则跳出
        
    //如果$url使用默认, 即空值, 则赋值为本页URL
    if(!$url){$url=$_SERVER["REQUEST_URI"];}

    //URL分析
    $parse_url = parse_url($url);
    $url_query = empty($parse_url['query'])?'':$parse_url['query']; //单独取出URL的查询字串

    if($url_query){
      //因为URL中可能包含了页码信息，我们要把它去掉，以便加入新的页码信息。
      //$url_query=ereg_replace("(^|&)$varPage=$nowPage", "", $url_query);
      $url_query=preg_replace("/(^|&)$varPage=\d*/", '', $url_query);
      
      //将处理后的URL的查询字串替换原来的URL的查询字串：
      $url=str_replace($parse_url["query"], $url_query, $url);

      //在URL后加page查询信息，但待赋值： 
      if($url_query) $url.="&$varPage"; else $url.=$varPage;
    }else {
      $url.="?$varPage";
    }
    $a = $nowPage-1;if($a<1){ $a=1; }
    $b = $nowPage+1;if($b>$totalPages){ $b=$totalPages; }
    $pagenav = " <a href='$url=1'>首页</a> ";
    if($a==$nowPage){
      $pagenav.=' 前页 ';
    }else{
      $pagenav.=" <a href='$url=$a'>前页</a> ";
    }
    if($b==$nowPage){
      $pagenav.=' 后页 ';
    }else{
      $pagenav.=" <a href='$url=$b'>后页</a> ";
    }
    $pagenav.=" <a href='$url=$totalPages'>尾页</a> ";

    //下拉跳转列表，循环列出所有页码：
    $pagenav.="到第 <select onchange='location.href=(\"$url=\"+this.value)'>\n";
    for($i=1;$i<=$totalPages;$i++){
      if($i==$nowPage){
        $pagenav.="<option value='$i' selected>$i</option>\n";
      }else{
        $pagenav.="<option value='$i'>$i</option>\n";
      }
    }
    $pagenav.="</select> 页, 共 $totalPages 页";
    $this->HTML .= $pagenav;
  }
}
?>