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

            <h1><span>Ученици</span></h1>

            <div class="single-box">
                <h2 id="strucna">Распоред пријема родитеља 2023/2024</h2>
                <div class="single-box" style="font-size: 18px">
                    [DOC] - <a href="files/2024/raspored_prijema_roditelja.doc" target="_blank">Распоред пријема родитеља 2023/2024</a> <br />
                </div>

                <!-- <table class="pure-table">
                <thead>
                    <tr>
                        <th>Име и презиме наставника</th>
                        <th>Наставни предмет / одељењски старешниа</th>
                        <th>Дан</th>
                        <th>Време</th>
                    </tr>
                </thead>

                <tbody>
                     <tr>
                        <td style="text-align:center; font-weight:600;  background:#e0e0e0; border-bottom:1px solid #cbcbcb; border-top:1px solid #cbcbcb" colspan="4">Млађи разреди</td>
                    </tr>
                    <tr>
                       <td>Славка Стојић</td>
                       <td>Други и трећи разред</td>
                       <td>Петак</td>
                       <td>5. час</td>
                    </tr>
                     <tr class="pure-table-odd">
                       <td>Гордана Стојковић</td>
                       <td>Први и четврти разред</td>
                       <td>Петак</td>
                       <td>4. час</td>
                    </tr>

                     <tr>
                        <td style="text-align:center; font-weight:600;  background:#e0e0e0; border-bottom:1px solid #cbcbcb; border-top:1px solid #cbcbcb" colspan="4">Старији разреди</td>
                    </tr>
                    <tr>
                       <td>Жељко Адамовић</td>
                       <td>Математика/одељ. старешина 5. разреда</td>
                       <td>Понедељак</td>
                       <td>3. час</td>
                    </tr>
                    <tr class="pure-table-odd">
                       <td>Иван Гентић</td>
                       <td>Физичко васпитање/одељ. старешина 8. разреда</td>
                       <td>Уторак</td>
                       <td>5. час</td>
                    </tr>
                    <tr>
                       <td>Катарина Ивановић</td>
                       <td>Српски језик/одељ. старешина 7. разреда</td>
                       <td>Понедељак<br></td>
                       <td>3. час</td>
                    </tr>                       
                     <tr class="pure-table-odd">
                       <td>Сања Максимовић Јовановић</td>
                       <td>Енглески језик/одељ. старешина 6. разреда</td>
                       <td>Понедељак<br></td>
                       <td>5. час</td>
                    </tr>
                    
                   <tr>
                       <td>Јелена Лекић</td>
                       <td>Историја </td>
                       <td>Среда</td>
                       <td>1. час</td>
                    </tr>
                    <tr class="pure-table-odd">
                       <td>Радица Вујетић</td>
                       <td>Физика</td>
                       <td>Петак</td>
                       <td>4. час</td>
                    </tr>
                    <tr >
                       <td>Весна Аџић</td>
                       <td>Француски језик</td>
                       <td>Четвртак</td>
                       <td>2. час</td>
                    </tr>
                     <tr class="pure-table-odd">
                       <td>Горан Лазаревић</td>
                       <td>Хемија</td>
                       <td>Петак </td>
                       <td>5.час</td>
                    </tr>                                    
                    <tr>
                       <td>Славица Ћурковић</td>
                       <td>Географија</td>
                       <td>Понедаљак</td>
                       <td></td>
                    </tr>
                    
                     <tr  class="pure-table-odd">
                       <td>Марија Аничић</td>
                       <td>Биологија/чувари природе</td>
                       <td>Понедељак</td>
                       <td>5.час</td>
                    </tr>                    
                    <tr>
                       <td>Биљана Теодоровић</td>
                       <td>Музичка култура</td>
                       <td>Четвртак</td>
                       <td>2. час</td>
                    </tr>
                     <tr class="pure-table-odd">
                       <td>Марија Гајић</td>
                       <td>Ликовна култура</td>
                       <td>Уторак</td>
                       <td>2. час</td>
                    </tr>                    
                    
                    <tr>
                       <td>Милоје Марић</td>
                       <td>ТИО и информатика</td>
                       <td>Уторак</td>
                       <td>2. час</td>
                    </tr>
                     <tr class="pure-table-odd">   
                       <td>Драган Петровић</td>
                       <td>Верска настава</td>
                       <td>Среда</td>
                       <td>5. час</td>
                    </tr>
                    <tr>
                       <td>Наташа Андрић Несторовић</td>
                       <td>Енглески језик</td>
                       <td>Понедељак</td>
                       <td>5. час</td>
                    </tr>

                    </tbody>
            </table> -->

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
