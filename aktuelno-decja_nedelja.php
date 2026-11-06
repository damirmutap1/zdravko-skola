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
            <h1><span>Дечја недеља</span></h1>
            
            <div class="single-box" style="margin-bottom:20px">
                <h2 id="direktor">Отварање и активности дечје недеље</h2>
                <p>
                    <strong>Понедељак, 1.10.2018.:</strong><br>

                    -Приредба добродошлице за ђаке праваке и отварање Дечје недеље.<br><br>

                    -Позоришна представа "Мачак у потрази за чизмама". Представи присуствовали сви ученици.<br><br>                    
                        
                    <strong>Уторак, 2.10.2018.:</strong><br>

                    -Јесењи вашар у организацији млађих разреда и предшколаца као и њихових родитеља. <br><br>

                    <strong>Среда, 3.10.2018.:</strong><br>

                    -Предавање представника Полицијске управе Ваљево за ученике 4. и 6. разреда у оквиру реализације програма Основи безбедности деце <br><br>

                    -Ученици млађих разреда и предшколци цртали су на бетону и кроз цртеже показали шта за њих представља срећно и здраво детињство. <br><br>

                    -Ученица четвртог разреда, Ирина Вујић, присуствовала је приредби у Центру за културу у Ваљеву где је добила специјалну похвалницу за литерарни рад Имам право. <br><br>
                    
                    <strong>Имам право</strong><br>

                    Да се играм имам право<br>
                    и да живим срећно, здраво,<br>
                    под облаком што се смеје<br>
                    да ме љубав људска греје.<br><br>

                    На крилима маште да се винем<br>
                    кроз висине, кроз дубине,<br>
                    да загрлим сву доброту,<br>
                    да посејем сву лепоту.<br><br>

                    Да одрастем имам право <br>
                    на Планети чистој, здравој,<br>
                    да не стрепим ја од сутра,<br>
                    да за срећна живим јутра.<br><br>



                    Вујић Ирина, четврти разред<br>
                    ОШ „Здравко Јовановић“ Поћута<br><br>

                    
                    <a href="galerija.php">Погледајте слике у нашој галерији </a><br>
                    
                </p>                
               
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
       

        
        
      $('.main-slider').owlCarousel({            
            nav:false,
            responsiveClass:true,
            dots:false,
            responsive:{
                0:{
                    items:1
                },
                600:{
                    items:1
                },
                1000:{
                    items:1
                }
            }
        });
        
        $('.galerija').owlCarousel({            
            nav:false,
            responsiveClass:true,
            dots:false,
            responsive:{
                0:{
                    items:2
                },
                600:{
                    items:4
                },
                1000:{
                    items:4
                }
            }
        });  
</script>
    
<script>
$(document).ready(function(){
  // Add smooth scrolling to all links
  $("a").on('click', function(event) {

    // Make sure this.hash has a value before overriding default behavior
    if (this.hash !== "") {
      // Prevent default anchor click behavior
      event.preventDefault();

      // Store hash
      var hash = this.hash;

      // Using jQuery's animate() method to add smooth page scroll
      // The optional number (800) specifies the number of milliseconds it takes to scroll to the specified area
      $('html, body').animate({
        scrollTop: $(hash).offset().top
      }, 800, function(){
   
        // Add hash (#) to URL when done scrolling (default click behavior)
        window.location.hash = hash;
      });
    } // End if
  });
});
</script>
    
    
    
</body>
</html>
