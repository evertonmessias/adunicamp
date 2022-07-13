  <!-- ======= Footer ======= -->
  <footer id="footer">

    <div class="footer-top">
      <div class="container">
        <div class="row">

          <div class="col-lg-6 col-md-6 footer-contact">
            <h3><?php echo get_option('portal_input_0'); ?></h3>
            <p>
            <?php echo get_option('portal_input_6'); ?>
            <br>
              <strong>Telefone:</strong> <?php echo get_option('portal_input_9'); ?><br>
              <strong>E-mail:</strong> <?php echo get_option('portal_input_10'); ?><br>
            </p>
          </div>

          <div class="col-lg-3 col-md-6 footer-links">
            <ul>
              <li><i class="bx bx-chevron-right"></i> <a href="#">Home</a></li>
              <li><i class="bx bx-chevron-right"></i> <a href="#">About us</a></li>
              <li><i class="bx bx-chevron-right"></i> <a href="#">Services</a></li>
              <li><i class="bx bx-chevron-right"></i> <a href="#">Terms of service</a></li>
              <li><i class="bx bx-chevron-right"></i> <a href="#">Privacy policy</a></li>
            </ul>
          </div>

          <div class="col-lg-3 col-md-6 footer-newsletter">
            <h4>Newsletter</h4>
            <form action="" method="post">
              <input type="email" name="email"><input type="submit" value="Subscribe">
            </form>
          </div>

        </div>
      </div>
    </div>

    <div class="container d-lg-flex py-4">

      <div class="me-lg-auto text-center text-lg-start">
        <div class="credits">
           <small>Designed by <a target="_blank" href="https://portfolio.evertonm.com/">EvM</a></small>
        </div>
      </div>
      <div class="social-links text-center text-lg-right pt-3 pt-lg-0">
        <a target="_blank" href="<?php echo get_option('portal_input_11'); ?>" class="facebook"><i class="bx bxl-facebook"></i></a>
        <a target="_blank" href="<?php echo get_option('portal_input_12'); ?>" class="instagram"><i class="bx bxl-instagram"></i></a>
        <a target="_blank" href="<?php echo get_option('portal_input_13'); ?>" class="youtube"><i class="bx bxl-youtube"></i></a>        
        <a target="_blank" href="<?php echo get_option('portal_input_14'); ?>" class="twitter"><i class="bx bxl-twitter"></i></a>
      </div>
    </div>
  </footer><!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="<?php echo SITEPATH; ?>assets/vendor/purecounter/purecounter.js"></script>
  <script src="<?php echo SITEPATH; ?>assets/vendor/aos/aos.js"></script>
  <script src="<?php echo SITEPATH; ?>assets/vendor/jquery/jquery.min.js"></script>
  <script src="<?php echo SITEPATH; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="<?php echo SITEPATH; ?>assets/vendor/jquery.easing/jquery.easing.min.js"></script>
  <script src="<?php echo SITEPATH; ?>assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="<?php echo SITEPATH; ?>assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <script src="<?php echo SITEPATH; ?>assets/vendor/waypoints/noframework.waypoints.js"></script>
  <script src="<?php echo SITEPATH; ?>assets/vendor/venobox/venobox.min.js"></script>
  <script src="<?php echo SITEPATH; ?>assets/vendor/swiper/swiper-bundle.min.js"></script>

  <!-- Template Main JS File -->
  <script src="<?php echo SITEPATH; ?>assets/js/main.js"></script>
  <script src="<?php echo SITEPATH; ?>assets/js/calendar.js"></script>

  <!-- Calendar -->
  <script>
    $(document).ready(function() {
      var date = new Date();
      var d = date.getDate();
      var m = date.getMonth();
      var y = date.getFullYear();

      /*  className colors
  
    className: default(transparent), important(red), chill(pink), success(green), info(blue)
  
    */


      /* initialize the external events
      -----------------------------------------------------------------*/

      $('#external-events div.external-event').each(function() {

        // create an Event Object (http://arshaw.com/fullcalendar/docs/event_data/Event_Object/)
        // it doesn't need to have a start or end
        var eventObject = {
          title: $.trim($(this).text()) // use the element's text as the event title
        };

        // store the Event Object in the DOM element so we can get to it later
        $(this).data('eventObject', eventObject);

        // make the event draggable using jQuery UI
        $(this).draggable({
          zIndex: 999,
          revert: true, // will cause the event to go back to its
          revertDuration: 0 //  original position after the drag
        });

      });


      /* initialize the calendar
      -----------------------------------------------------------------*/

      var calendar = $('#calendar').fullCalendar({
        header: {
          left: 'title',
          //center: 'agendaDay,agendaWeek,month',
          right: 'prev,next today'
        },
        editable: true,
        firstDay: 1, //  1(Monday) this can be changed to 0(Sunday) for the USA system
        selectable: true,
        defaultView: 'month',

        axisFormat: 'h:mm',
        columnFormat: {
          month: 'ddd', // Mon
          week: 'ddd d', // Mon 7
          day: 'dddd M/d', // Monday 9/7
          agendaDay: 'dddd d'
        },
        titleFormat: {
          month: 'MMMM yyyy', // September 2009
          week: "MMMM yyyy", // September 2009
          day: 'MMMM yyyy' // Tuesday, Sep 8, 2009
        },
        allDaySlot: false,
        selectHelper: true,
        /*select: function (start, end, allDay) {
            var title = prompt('Event Title:');
            if (title) {
                calendar.fullCalendar('renderEvent',
                    {
                        title: title,
                        start: start,
                        end: end,
                        allDay: allDay
                    },
                    true // make the event "stick"
                );
            }
            calendar.fullCalendar('unselect');
        },*/

        droppable: true, // this allows things to be dropped onto the calendar !!!
        drop: function(date, allDay) { // this function is called when something is dropped

          // retrieve the dropped element's stored Event Object
          var originalEventObject = $(this).data('eventObject');

          // we need to copy it, so that multiple events don't have a reference to the same object
          var copiedEventObject = $.extend({}, originalEventObject);

          // assign it the date that was reported
          copiedEventObject.start = date;
          copiedEventObject.allDay = allDay;

          // render the event on the calendar
          // the last `true` argument determines if the event "sticks" (http://arshaw.com/fullcalendar/docs/event_rendering/renderEvent/)
          $('#calendar').fullCalendar('renderEvent', copiedEventObject, true);

          // is the "remove after drop" checkbox checked?
          if ($('#drop-remove').is(':checked')) {
            // if so, remove the element from the "Draggable Events" list
            $(this).remove();
          }

        },

        events: [

          <?php echo listCalendar() ?>

        ],
      });


    });
  </script>

  <?php wp_footer(); ?>
  </body>

  </html>