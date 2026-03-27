		<!-- Essential jQuery Plugins
		================================================== -->
		<!-- Main jQuery -->
        <script src="js/jquery-1.11.1.min.js"></script>
		<!-- Twitter Bootstrap -->
        <script src="js/bootstrap.min.js"></script>
		<!-- Single Page Nav -->
        <script src="js/jquery.singlePageNav.min.js"></script>
		<!-- jquery.fancybox.pack -->
        <script src="js/jquery.fancybox.pack.js"></script>
		<!-- Owl Carousel -->
        <script src="js/owl.carousel.min.js"></script>
        <!-- jquery easing -->
        <script src="js/jquery.easing.min.js"></script>
        <!-- Fullscreen slider -->
        <script src="js/jquery.slitslider.js"></script>
        <script src="js/jquery.ba-cond.min.js"></script>
		<!-- onscroll animation -->
        <script src="js/wow.min.js"></script>
		<!-- Custom Functions -->
        <script src="js/main.js"></script>
		<script>
			document.querySelectorAll('.update-inventory-button').forEach(button => {
			  button.addEventListener('click', function (e) {
				e.preventDefault();
			
				const clock = document.getElementById('clock');
				clock.style.display = 'block';
			
				// Restart video for iOS
				const video = document.getElementById('loading-video');
				if (video) {
				  video.pause();
				  video.currentTime = 0;
				  video.play();
				}
			
				// Preserve submit button name/value
				const tempInput = document.createElement('input');
				tempInput.type = 'hidden';
				tempInput.name = button.name;
				tempInput.value = button.value;
			
				const form = button.closest('form');
				form.appendChild(tempInput);
			
				// Submit after a short delay
				setTimeout(() => form.submit(), 100);
			  });
			});
        </script>
		<script>
        // Auto-scroll mobile menu when Other Tools is expanded
        $(document).ready(function() {
            // Only on mobile
            if ($(window).width() < 768) {
                $('.dropdown > a').on('click', function() {
                    setTimeout(() => {
                        $('.navbar-collapse').animate({
                            scrollTop: $('.navbar-collapse')[0].scrollHeight
                        }, 300);
                    }, 100);
                });
            }
        });
        </script>