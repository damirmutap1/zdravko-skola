<?php require_once __DIR__ . '/includes/app.php'; cms_maybe_render_managed_page(pathinfo(__FILE__, PATHINFO_FILENAME), ['source_file' => __FILE__]); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">


    <title>Основна школа Здравко Јовановић </title>
    

    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700&amp;subset=cyrillic" rel="stylesheet">   
    <link href="https://fonts.googleapis.com/css?family=PT+Sans:400,700&amp;subset=cyrillic-ext" rel="stylesheet"> 
  <link href="https://fonts.googleapis.com/css?family=Alice&amp;subset=cyrillic,cyrillic-ext" rel="stylesheet"> 
    
    
    
     <link href="stylesheets/screen.css" rel="stylesheet">
    <link href="css/tables.css" rel="stylesheet">
    
        <!-- OWL SLIDER PLUGIN -->
    <link rel="stylesheet" href="css/owl.carousel.css">
    <link rel="stylesheet" href="css/owl.theme.default.css">
    
    <link type="text/css" rel="stylesheet" href="css/responsive-tabs/responsive-tabs.css" />
    <link type="text/css" rel="stylesheet" href="css/responsive-tabs/style.css" />
    <link rel="stylesheet" href="css/prettyPhoto.css">
    
</head>

<body>

     <?php include('includes/header.php')?>
    
   <div class="clearfix"></div>

    
    <section class="cover-istorijat">
       <div class="wrap">
           <img src="images/istorijat.jpg"> 
         
       </div>
    </section>
    
    <section class="single-page">
        <div class="wrap">   
            <h1><span>Галерија</span></h1>
            
            
            <div class="single-box">
                
                <div id="horizontalTab">
                <ul>
                    <li><a href="#tab-1">Дечија недеља 2020</a></li>    
                    <li><a href="#tab-2">Разна дешавања</a></li>    
                    <li><a href="#tab-3">Крос</a></li>           
                    <li><a href="#tab-4">Приредба за прваке</a></li> 
                    <li><a href="#tab-5">Позоришна представа</a></li>  
                    <li><a href="#tab-6">Јесењи вашар</a></li>  
                    <li><a href="#tab-7">Цртање на бетону</a></li>  
                    <li><a href="#tab-8">Савиндан 2021</a></li>  
                </ul>
                
                <div id="tab-1">
                    <?php include("_decja-nedelja-2020-gal.php"); ?>                    
                </div>    
                    
                <div id="tab-2">
                    <?php include("_razno-gal.php"); ?>                    
                </div>

                <div id="tab-3">
                    <?php include("_kros-gal.php"); ?>                    
                </div>
                <div id="tab-4">
                    <?php include("_priredba-gal.php"); ?>
                </div>
                <div id="tab-5">
                    <?php include("_pozorisna-gal.php"); ?>
                </div>
                <div id="tab-6">
                      <?php include("_vasar-gal.php"); ?>  
                </div>
                <div id="tab-7">
                      <?php include("_crtanje-gal.php"); ?>  
                </div>
                <div id="tab-8">
                      <?php include("_savindan-2021-gal.php"); ?>  
                </div>
                  

            </div>    
               
                
            </div>
         
           
             <div class="clearfix"></div>
            
        </div>
    </section>
    
    

    <div class="clearfix"></div>
    
   <?php include('includes/footer.php')?>   
    
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="js/owl-slider/owl.carousel.min.js"></script>
    <script>
        var w, pos;
        
        
        /*Inicializuj poziciju okvira aktivnog meni bara*/
        $( document ).ready(function() {             
                pos = $(".main_nav.activ").position().left;
                w = $(".main_nav.activ").width();

                $("a span.activ:nth-child(1)").css("left",pos+5);
                $("a span.activ:nth-child(2)").css("left",pos+w+50);
                $("a span.activ:nth-child(3)").css("left",pos+w+50);
                $("a span.activ:nth-child(4)").css("left",pos+5);
           
        });
        /*Na resize prati poziciju aktivnog dugmeta*/
        $( window ).resize(function() {
                pos = $(".main_nav.activ").position().left;
                w = $(".main_nav.activ").width();

                $("a span.activ:nth-child(1)").css("left",pos+5);
                $("a span.activ:nth-child(2)").css("left",pos+w+50);
                $("a span.activ:nth-child(3)").css("left",pos+w+50);
                $("a span.activ:nth-child(4)").css("left",pos+5);
        });
    
        /*hover menu efekat*/    
        $(".main_nav").hover(function(){
            pos = $(this).position().left;
            w = $(this).width();

            $("a span.in-active:nth-child(1)").css("left",pos+5);
            $("a span.in-active:nth-child(2)").css("left",pos+w+50);
            $("a span.in-active:nth-child(3)").css("left",pos+w+50);
            $("a span.in-active:nth-child(4)").css("left",pos+5);
            
        });
        
        $(".button a").hover(function(){
            pos = $(this).position().left;
            w = $(this).width();

            $("a span.buton:nth-child(1)").css("left",pos);
            $("a span.buton:nth-child(2)").css("left",pos+w+16);
            $("a span.buton:nth-child(3)").css("left",pos+w+16);
            $("a span.buton:nth-child(4)").css("left",pos);
            
        });
     
</script>
    
   <!-- prettyPhoto plugin -->
    <script src="js/photo-plugin/jquery.prettyPhoto.js"></script>
    <script type="text/javascript" charset="utf-8">
          $(document).ready(function(){
            $("a[rel^='prettyPhoto']").prettyPhoto({
                social_tools:false    
            });      
          });
    </script> 
      <!-- Responsive Tabs JS -->
    <script src="js/responsive-tabs/jquery.responsiveTabs.js" type="text/javascript"></script>

    <script type="text/javascript">
        $(document).ready(function () {
            var $tabs = $('#horizontalTab');
            $tabs.responsiveTabs({
                rotate: false,
                startCollapsed: 'accordion',
                collapsible: 'accordion',
                setHash: true,
                activate: function(e, tab) {
                    $('.info').html('Tab <strong>' + tab.id + '</strong> activated!');
                },
                activateState: function(e, state) {
                    //console.log(state);
                    $('.info').html('Switched from <strong>' + state.oldState + '</strong> state to <strong>' + state.newState + '</strong> state!');
                }
            });
            $('#enable-tab').on('click', function() {
                $tabs.responsiveTabs('enable', 1);
            });
            $('#disable-tab').on('click', function() {
                $tabs.responsiveTabs('disable', 1);
            });
            $('.select-tab').on('click', function() {
                $tabs.responsiveTabs('activate', $(this).val());
            });

        });
    </script>
    
    
</body>
</html>
