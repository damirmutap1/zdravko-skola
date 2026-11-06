<?php
session_start();
include('_domain.php'); 



    if (empty($_SESSION['user_id'])){
        $random = rand(10000, 100000);
        $time = date("hsi");
        $_SESSION['user_id']='cart_'.$time.$random;
    }

    
 if(!empty($_GET['dimenzija']))  { $dimenzija_slug  = $_GET['dimenzija']; }  else{ $dimenzija_slug  = '0';}
 if(!empty($_GET['kategorija'])) { $kategorija = $_GET['kategorija']; } else{ $kategorija = '0';}
 if(!empty($_GET['pod_kategorija'])) { $pod_kategorija = $_GET['pod_kategorija']; } else{ $pod_kategorija = '0';}

    if(!empty($_GET['lang']) || isset($_GET['lang'])){
        $lang= $_GET['lang'];
    }else{
        $lang = 'en';
    }


?>
<!DOCTYPE HTML>
<html lang="en">
	<head>
        <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-138308325-1"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'UA-138308325-1');
    </script>
        <meta charset="UTF-8">
		<title>Blue Molds® | Products</title>
        <link href="<?php echo $domain?>stylesheets/screen.css" rel="stylesheet">
         <link href="<?php echo $domain?>css/owl.carousel.css" rel="stylesheet">
        
         <meta name="viewport" content="width=device-width, initial-scale=1.0">
        
        
        <meta http-equiv="cache-control" content="no-cache"/>
        <meta name="description" content="Blue Molds® products can be used to effectively reduce concrete waste and to create new products and added value.">
        <meta name="keywords" content="legostein beton schalung, beton block, betonblock, concrete, block, molds, lifting devices,dividers, accessories, steel molds,"/>
        <link rel="canonical" href="http://www.bluemolds.com/products.php" />
        
        <meta property="og:title" content="Blue Molds® | Products" />
        <meta property="og:description" content="Blue Molds® products can be used to effectively reduce concrete waste and to create new products and added value." />
        <meta property="og:url" content="http://www.bluemolds.com/products.php" />
        <meta property="og:image" content="http://www.bluemolds.com/images/blu-molds-cover-share.jpg" />
        
        <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" rel="stylesheet"> 
	</head>
 
	<body <?php if($lang == 'ru') echo 'class="ru"'?>>
       <?php include('includes/header.php')?>
        
        <div class="clearfix"></div>
        
                
        <?php include('includes/navigation.php')?>
      

    <div class="image-bg">
        <img src="<?php echo $domain?>images/pozadina.png">    
    </div> 
        
    <div id="popup-cart">
    </div>    
        
    <section class="main-body">
      
        
        <!--div class="vat">
            
        </div-->
        <div class="wrap">           
            <div class="left no-mob">
              <?php
                    if($lang == 'sl'){echo '<h2>Izdelek</h2>';}
                    else if($lang == 'sl'){echo '<h2>Produkt</h2>';}
                    else if($lang == 'nl'){echo '<h2>Artikel</h2>';}
                    else if($lang == 'esp'){echo '<h2>Producto</h2>';}
                    else if($lang == 'ru'){echo '<h2>Товар</h2>';}
                    else if($lang == 'it'){echo '<h2>Prodotto</h2>';}
                    else if($lang == 'fr'){echo '<h2>Produit</h2>';}
                    else{echo '<h2>Product</h2>';}
                ?>
                <hr>
                <ul class="left-filter">
                    <?php
                         include('admin/_config/db.php'); 
                         $result = $con->prepare("SELECT * FROM kategorije ORDER BY sort ASC"); 
                         $result->execute();
                         while ($row = $result->fetch(PDO::FETCH_ASSOC)){
                             
                             if($lang == 'sl'){$naziv_kategorije = $row['sl'];}
                             else if($lang == 'de'){$naziv_kategorije = $row['de'];}
                             else if($lang == 'nl'){$naziv_kategorije = $row['nl'];}
                             else if($lang == 'esp'){$naziv_kategorije = $row['esp'];}
                             else if($lang == 'ru'){$naziv_kategorije = $row['ru'];}
                             else if($lang == 'it'){$naziv_kategorije = $row['it'];}
                             else if($lang == 'fr'){$naziv_kategorije = $row['fr'];}
                             else{$naziv_kategorije = $row['naziv'];}
                             
                             $id_kategorije = $row['id'];
                             $slug = $row['slug'];
                             
                             
                             
                             
                             $result1 = $con->prepare("SELECT * FROM proizvod WHERE id_kategorija = $id_kategorije AND active = 1 "); 
                             
                             
                             $result1->execute();
                             $count = $result1->rowCount();
                             
                             if ($kategorija == $slug){$id_kategorije_select = $id_kategorije;}      
                             
                             
                            
                             
                             if ($lang == 'sl'){
                                 echo '<a href="'.$domain.'sl/products.php?kategorija='.$slug.'"><li class="cat-text"><strong>'.$naziv_kategorije.'</strong> ('.$count.')</li></a>';
                             }else if ($lang == 'de'){
                                  echo '<a href="'.$domain.'de/products.php?kategorija='.$slug.'"><li class="cat-text"><strong>'.$naziv_kategorije.'</strong> ('.$count.')</li></a>';
                             }else if ($lang == 'nl'){
                                 echo '<a href="'.$domain.'nl/products.php?kategorija='.$slug.'"><li class="cat-text"><strong>'.$naziv_kategorije.'</strong> ('.$count.')</li></a>';
                             }else if ($lang == 'esp'){
                                 echo '<a href="'.$domain.'esp/products.php?kategorija='.$slug.'"><li class="cat-text"><strong>'.$naziv_kategorije.'</strong> ('.$count.')</li></a>';
                             }else if ($lang == 'ru'){
                                 echo '<a href="'.$domain.'ru/products.php?kategorija='.$slug.'"><li class="cat-text"><strong>'.$naziv_kategorije.'</strong> ('.$count.')</li></a>';
                             }else if ($lang == 'it'){
                                 echo '<a href="'.$domain.'it/products.php?kategorija='.$slug.'"><li class="cat-text"><strong>'.$naziv_kategorije.'</strong> ('.$count.')</li></a>';
                             }else if ($lang == 'fr'){
                                 echo '<a href="'.$domain.'fr/products.php?kategorija='.$slug.'"><li class="cat-text"><strong>'.$naziv_kategorije.'</strong> ('.$count.')</li></a>';
                             }else{                                  
                                 echo '<a href="'.$domain.'products.php?kategorija='.$slug.'"><li class="cat-text"><strong>'.$naziv_kategorije.'</strong> ('.$count.')</li></a>';
                             }
                             
                             //PODKATEGORIJE
                             $result_pod_cat = $con->prepare("SELECT * FROM pod_cat WHERE id_cat = :id_kategorije"); 
                             $result_pod_cat -> bindParam(':id_kategorije',$id_kategorije);
                             $result_pod_cat -> execute();
                             

                            while ($row_pod_cat = $result_pod_cat->fetch(PDO::FETCH_ASSOC)){      
                                //POKUPI BROJ PROIZVODA U POD KATEGORIJI
                                 $result_podcat_count = $con->prepare("SELECT * FROM proizvod WHERE id_pod_cat = :id_pod_cat"); 
                                 $result_podcat_count -> bindParam(':id_pod_cat',$row_pod_cat['id']);
                                 $result_podcat_count -> execute();
                                 $count_pod = $result_podcat_count->rowCount();
                                                                
                                
                                
                                echo '<a href="'.$domain.'products.php?pod_kategorija='.$row_pod_cat['slug'].'" class="pod_cat_menu"><li> - '.$row_pod_cat['naziv'].'('.$count_pod.')</li></a>';
                            }
                    
                   } 
                    ?>
                </ul>
                <?php
                    if($lang == 'sl'){echo '<h2>Velikost plesni</h2>';}
                    else if($lang == 'de'){echo '<h2>Formgrößen</h2>';}
                    else if($lang == 'nl'){echo '<h2>Product afmetingen</h2>';}
                    else if($lang == 'esp'){echo '<h2>Tamaño del molde / encofrado</h2>';}
                    else if($lang == 'ru'){echo '<h2>Размеры форм/матриц</h2>';}
                    else if($lang == 'it'){echo '<h2>Formwork / formwork dimensions</h2>';}
                    else if($lang == 'fr'){echo '<h2>Taille du moule / coffrage</h2>';}
                    else{echo '<h2>Mold Size</h2>';}
                ?>
                
                <hr>
                <ul class="left-filter">
                    <?php
                    include('admin/_config/db.php'); 
                        if ($kategorija === '0' || $kategorija === 'all'){

                          $result = $con->prepare("SELECT * FROM dimenzije ORDER BY id_kategorije ASC, dimenzija ASC"); 
                         
                        }else{
                            
                            $result = $con->prepare("SELECT * FROM dimenzije WHERE id_kategorije = :id_kategorije ORDER BY dimenzija ASC"); 
                            $result -> bindParam(':id_kategorije',$id_kategorije_select);
                        }
                    
                        $result->execute();
                         while ($row = $result->fetch(PDO::FETCH_ASSOC)){
                             $dimenzija = $row['dimenzija'];
                             $id_kategorije = $row['id'];
                             $slug = $row['slug'];
                             
                             
                            
                                                          
                             //Get category slug
                             $cat_id = $row['id_kategorije'];
                             
                             
                                 $result2 = $con->prepare("SELECT slug FROM kategorije WHERE id = $cat_id"); 
                                 $result2->execute();
                                 $row2=$result2->fetch();
                                 $kategorija_filter =$row2['slug'];
                             
                             
                             $result1 = $con->prepare("SELECT * FROM proizvod WHERE id_dimenzija = $id_kategorije"); 
                             $result1->execute();
                             $count = $result1->rowCount();
                             
                            
                             if ($lang == 'sl'){
                                 echo '<a href="'.$domain.'sl/products.php?kategorija='.$kategorija_filter.'&dimenzija='.$slug.'"><li>'.$dimenzija.' ('.$count.')</li></a>';
                             }else if ($lang == 'de'){
                                 echo '<a href="'.$domain.'de/products.php?kategorija='.$kategorija_filter.'&dimenzija='.$slug.'"><li>'.$dimenzija.' ('.$count.')</li></a>';
                             }else if ($lang == 'nl'){
                                 echo '<a href="'.$domain.'nl/products.php?kategorija='.$kategorija_filter.'&dimenzija='.$slug.'"><li>'.$dimenzija.' ('.$count.')</li></a>';
                             }else if ($lang == 'esp'){
                                 echo '<a href="'.$domain.'esp/products.php?kategorija='.$kategorija_filter.'&dimenzija='.$slug.'"><li>'.$dimenzija.' ('.$count.')</li></a>';
                             }else if ($lang == 'ru'){
                                 echo '<a href="'.$domain.'ru/products.php?kategorija='.$kategorija_filter.'&dimenzija='.$slug.'"><li>'.$dimenzija.' ('.$count.')</li></a>';
                             }else if ($lang == 'it'){
                                 echo '<a href="'.$domain.'it/products.php?kategorija='.$kategorija_filter.'&dimenzija='.$slug.'"><li>'.$dimenzija.' ('.$count.')</li></a>';
                             }else if ($lang == 'fr'){
                                 echo '<a href="'.$domain.'fr/products.php?kategorija='.$kategorija_filter.'&dimenzija='.$slug.'"><li>'.$dimenzija.' ('.$count.')</li></a>';
                             }else{
                                echo '<a href="'.$domain.'products.php?kategorija='.$kategorija_filter.'&dimenzija='.$slug.'"><li>'.$dimenzija.' ('.$count.')</li></a>';
                             }
                             if ($dimenzija_slug == $slug){$id_dimenzija_select = $id_kategorije;}    
                            
                       
                   } 
                      $con = null;
                    ?>
                    
                </ul>
            </div>
            
            
            <div class="right">
                
            <?php
                include('lang/product-lang.php');
            ?>
                
                
            <?php
                include('lang/mob-product.php');    
            ?>    
                
                
                
                <?php
                         include('admin/_config/db.php'); 
                
                     
                    if ($kategorija === '0' && $dimenzija_slug === '0' && $pod_kategorija === '0'){
                         $result = $con->prepare("SELECT * FROM proizvod WHERE active = 1 ORDER BY naziv ASC"); 
                       
                    }                 
                    if ($kategorija != '0' && $dimenzija_slug === '0' ){                       
                        $result = $con->prepare("SELECT * FROM proizvod WHERE id_kategorija = :id_kategorija AND active = 1 ORDER BY  naziv ASC"); 
                        $result -> bindParam(':id_kategorija',$id_kategorije_select);                       
                    }
                    if ($kategorija != '0' && $dimenzija_slug != '0' ){                       
                        $result = $con->prepare("SELECT * FROM proizvod WHERE id_kategorija = :id_kategorija AND id_dimenzija = :id_dimenzija AND active = 1 ORDER BY naziv ASC"); 
                        $result -> bindParam(':id_kategorija',$id_kategorije_select);    
                        $result -> bindParam(':id_dimenzija',$id_dimenzija_select);    
                    }
                    if ($pod_kategorija != '0'){      
                         //POKUPI ID PODKATEGORIJE PO NAZIVU
                        $result_cat_naziv = $con->prepare("SELECT id FROM pod_cat WHERE slug = :pod_kategorija"); 
                        $result_cat_naziv -> bindParam(':pod_kategorija',$pod_kategorija); 
                        $result_cat_naziv -> execute();
                             
                        $id_pod_kategorije = $result_cat_naziv -> fetch(PDO::FETCH_ASSOC);
                                                
                        $result = $con->prepare("SELECT * FROM proizvod WHERE id_pod_cat = :pod_kategorija AND active = 1 ORDER BY naziv ASC"); 
                        $result -> bindParam(':pod_kategorija',$id_pod_kategorije['id']);    
 
                    }
                
   

                    if ($kategorija == 'all'){
                         $result = $con->prepare("SELECT * FROM proizvod WHERE active = 1 ORDER BY id_kategorija ASC, naziv ASC");                        
                    }
                
                        $result->execute();
                         $n=0;
                
                         $prolaz_ime=0;
                         $prolaz_ime_pod_cat=0;
                
                         while ($row = $result->fetch(PDO::FETCH_ASSOC)){
                             $id = $row['id'];
                             $id_kategorije = $row['id_kategorija'];
                             $id_pod_kategorije = $row['id_pod_cat'];
                             $naziv = $row['naziv'];
                             $cena = $row['cena'];
                             $opis = strip_tags($row['opis']);
                             $slika_front = $row['slika_front'];  
                             $slika_back = $row['slika_back'];
                             $kol_proizvoda = $row['kolicina'];
                             $n++;
                             
                             
                             if($lang == 'en'){
                                 $opis = strip_tags($row['opis']);
                             }else{
                             
                                 $result_opis = $con->prepare("SELECT * FROM product_lang WHERE id_prod = :id_prod AND lang = :lang"); 
                                 $result_opis -> bindParam(':id_prod',$id);    
                                 $result_opis -> bindParam(':lang',$lang);    
                                 $result_opis -> execute();
                                 $opis_lang = $result_opis->fetch();
                                    
                                 //Ako je opis prazan za jezike vuci enegleski opis koji je vec po def setovan
                                 if($opis_lang['text'] != ''){
                                    $opis = $opis_lang['text'];
                                 }
                             }
                         
                             //pronadji NEW i dodaj strong
                             $mystring = '<strong>NEW!</strong>';
                             $findme = 'NEW!';                             
                             $pos = strpos($opis, $findme);
                             if ($pos === false) {
                                 //JEDIGOVNA I NE RADI NISTA                                 
                             }else{
                                 // Pronajdi govance i podebljaj ga
                                 $opis=substr_replace($opis, $mystring, 0, 4) . "<br />\n";
                             }
                             
                             
                             
                             if($kol_proizvoda == 0){$lager = 0;}else{$lager=1;}
                             
                             
                             
                         
                        //POKUPI NAZIV KATEGORIJE
                         
                        if ($lang == 'en'){
                             $result_cat_naziv = $con->prepare("SELECT * FROM kategorije WHERE id = :id_kategorije"); 
                        }else{
                            $result_cat_naziv = $con->prepare("SELECT * FROM kategorije WHERE id = :id_kategorije AND $lang = $lang");                            
                        }
                        $result_cat_naziv -> bindParam(':id_kategorije',$id_kategorije); 
                        $result_cat_naziv -> execute();
                             
                        $ime_kategorije = $result_cat_naziv -> fetch(PDO::FETCH_ASSOC);
                             
                        if($lang == 'sl'){$naziv_lang = $ime_kategorije['sl'];}
                        else if($lang == 'de'){$naziv_lang = $ime_kategorije['de'];}
                        else if($lang == 'nl'){$naziv_lang = $ime_kategorije['nl'];}
                        else if($lang == 'esp'){$naziv_lang = $ime_kategorije['esp'];}
                        else if($lang == 'ru'){$naziv_lang = $ime_kategorije['ru'];}
                        else if($lang == 'it'){$naziv_lang = $ime_kategorije['it'];}
                        else if($lang == 'fr'){$naziv_lang = $ime_kategorije['fr'];}
                        else{$naziv_lang = $ime_kategorije['naziv'];}     
                             
                        
                        if(!empty($staro_ime)){
                            if($staro_ime != $naziv_lang) $prolaz_ime=0;
                        }
                             
                        $staro_ime=$naziv_lang;    
                             
                         //STAMPAJ NAZIV KATEGORIJE
                        if($prolaz_ime == 0){     
                            echo '<p class="category_proizvod"><strong>'.$staro_ime.'</strong></p>';
                            $prolaz_ime++;
                        } 
                             
                        if ($id_pod_kategorije != 0 ){ 
                             //POKUPI NAZIV PODKATEGORIJE
                            
                            
                            $result_podcat_naziv = $con->prepare("SELECT naziv FROM pod_cat WHERE id = :id_pod_kategorije"); 
                            $result_podcat_naziv -> bindParam(':id_pod_kategorije',$id_pod_kategorije); 
                            $result_podcat_naziv -> execute();

                            $ime_podkategorije = $result_podcat_naziv -> fetch(PDO::FETCH_ASSOC);

                            if(!empty($staro_ime_pod_cat)){
                                if($staro_ime_pod_cat != $ime_podkategorije['naziv']) $prolaz_ime_pod_cat=0;
                            }

                            $staro_ime_pod_cat=$ime_podkategorije['naziv'];  

                             //STAMPAJ NAZIV PODKATEGORIJE
                             if($prolaz_ime_pod_cat == 0){     
                                echo '<p class="pod_category_proizvod"><strong>'.$staro_ime_pod_cat.'</strong></p>';
                                $prolaz_ime_pod_cat++;
                            }    
                        }
                             
                       echo '<div class="one-three-box">';           
                    ?>
                
                 <a href="#" data-lager="<?php echo $lager?>" data-opis="<?php echo $opis?>" data-lang="<?php echo $lang?>" data-cart="<?php echo $_SESSION['user_id']?>" data-sifra="<?php echo $id?>"  data-cena="<?php echo $cena?>"  data-img="<?php echo $domain.$slika_front?>" data-naziv="<?php echo $naziv?>" id="popup_cart" onclick="popup(this);">
                    <div class="img-box">
                        
                        <img src="<?php echo $domain.$slika_front?>" class="img-active">
                        <?php
                            if($slika_back !=""){echo '<img src="'.$domain.$slika_back.'" class="img-hover">';}else{
                                echo '<img src="'.$domain.$slika_front.'" class="img-hover">';
                            }
                        ?>
                        <div class="opis">
                            <?php
                                echo $opis;
                            ?>
                        </div>
                    </div>
                      
                    <div class="add-cart <?php if($lang == 'ru') echo 'ru'?>">
                       
                       <img src="<?php echo $domain?>images/ikone/cart-product.png"> <span class="no-hover">|</span> <span style="position:relative; top:-2px;">
                       
                           <?php
                            if($lang == 'sl'){ echo 'Dodaj v košarico';}
                            else if($lang == 'de'){echo 'In den Warenkorb legen';}
                            else if($lang == 'nl'){echo 'In de winkelmand';}
                            else if($lang == 'esp'){echo 'Añadir a la cesta';}
                            else if($lang == 'ru'){echo 'Добавить в корзину';}
                            else if($lang == 'it'){echo 'Aggiungi al carrello';}
                            else if($lang == 'fr'){echo 'Ajouter au panier';}
                            else{echo ' Add to cart: ';}
                        ?>
                        </span>                        
                    </div>
                </a>
                    <span class="name-product"><?php echo $naziv?></span>
                    <span class="price">€ 
                        <?php                        
                            $cena_print = round($cena);
                            $value= $cena_print - $cena;

                            if ($value < 1){
                                $cena_print = $cena;
                            }else{
                                $cena_print = $cena.'.00';
                            }
                            if ($value == 0){
                                $cena_print = $cena.'.00';
                            }
                             
                            echo $cena_print;
                        
                        ?>
                    
                    </span>

                </div>   
                <?php
                      } 
                      $con = null;                
                ?>
        
            </div>
           
        </div> 
          <div class="clearfix"></div>
    </section>
        <div class="clearfix"></div>
        
   <?php include('includes/footer.php')?>
        
        
        
        <script src="https://ajax.aspnetcdn.com/ajax/jQuery/jquery-3.4.0.min.js"></script>


         <script src="<?php echo $domain?>js/resize.js"></script>
        <script>
               $(function() {
                   $("ul.nav li a").hover(function() {
                        $(this).append('<span class="active"></span>');  
                    },function(){
                       $("ul.nav li a span.active").empty().remove();
               });
                   
                 $("ul.nav li ").hover(function() {
                        $(this).append(' <span class="molds"></span>');  
                    },function(){
                       $("ul.nav li  span.molds").empty().remove();
               });       
                   
          });  
            
            
            $( ".one-three-box" ).hover(
              function() {
                  var h;
                  h =$( 'a .opis' ).outerHeight();
                  $('a .opis').css('height',h);
                  $('a .opis').css('margin-top',-h-8);
              }, function() {
                  $('a .opis').css('height','auto');
                  $('a .opis').css('margin-top','auto');
              }
            );
              
  
    var active = 0;
            
    function popup(d){
      var cena = d.getAttribute("data-cena");
      var opis = d.getAttribute("data-opis");
      var img = d.getAttribute("data-img");
      var naziv = d.getAttribute("data-naziv");
      var sifra = d.getAttribute("data-sifra");
      var lager = d.getAttribute("data-lager");
      var lang = d.getAttribute("data-lang");
      var cart = d.getAttribute("data-cart");
       
        $('.cart-info-up').css('display','none');  
        active=0;
        
          $.ajax({ 
               type:'POST',             
               url:'<?php echo $domain?>includes/add_popup.php',                
               data:'cena='+cena+'&opis='+opis+'&naziv='+naziv+'&img='+img+'&sifra='+sifra+'&lager='+lager+'&lang='+lang+'&cart='+cart,
               success:function(html){
                    $('#popup-cart').html(html);
                
                }
            }); 
    }
            
    function delete_item(del){

      var id    = del.getAttribute("data-id");
      var id_2  = del.getAttribute("data-id_2");
      var lang  = del.getAttribute("data-lang");
        
          $.ajax({
                type:'POST',
                url:'<?php echo $domain?>includes/remove_item.php',
                data:'id='+id+'&id_2='+id_2+'&lang='+lang,
                success:function(html){
                    $('#sec-nav').html(html);
                
                }
            }); 
    }
                
    
                
    $('#cart-info').click(function() {
        if (active == 0){
            $('.cart-info-up').css('display','block');
            active =1;
            
        }else{
            $('.cart-info-up').css('display','none');
            active = 0;
        }             
    });
            
            
            
    var img_size;        
     $( document ).ready(function() {        
         
        img_size = $('.img-active').width();        
        img_size = (600/img_size);        
        img_size = 400 / img_size;     
         $('.img-box').css('height',img_size);
    });
            
     $( window ).resize(function() {
        img_size = $('.img-active').width();        
        img_size = (600/img_size);        
        img_size = 400 / img_size;
      
         
         $('.img-box').css('height',img_size);
    });
            

    
</script>
	</body>
</html>



			