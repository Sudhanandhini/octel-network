(function() {
	"use strict";
    
    window.onload = function(){

        /*------------------------------------------------
               Sticky Header
        ------------------------------------------------*/
        const getHeaderId = document.querySelector(".navbar-area");
        if (getHeaderId) {
            window.addEventListener('scroll', event => {
                const height = 150;
                const { scrollTop } = event.target.scrollingElement;
                document.querySelector('#navbar').classList.toggle('sticky', scrollTop >= height);
            });
        }

        /*------------------------------------------------
                Back to Top
        ------------------------------------------------*/
        let progressPath = document.getElementById("progress-path");
        let progressWrap = document.getElementById("progress-wrap");
        let pathLength = progressPath.getTotalLength();
        progressPath.style.transition = progressPath.style.webkitTransition = "none";
        progressPath.style.strokeDasharray = pathLength + " " + pathLength;
        progressPath.style.strokeDashoffset = pathLength;
        progressPath.getBoundingClientRect();
        progressPath.style.transition = progressPath.style.webkitTransition =
        "stroke-dashoffset 10ms linear";
        
        const onScollEvent = function (event) {
            let scroll = window.scrollY;
            let height = document.body.scrollHeight - window.innerHeight;
            let progress = pathLength - (scroll * pathLength) / height;
            progressPath.style.strokeDashoffset = progress;

            let offset = 50;
                if (window.scrollY > offset) {
                progressWrap.classList.add("active-progress");
                } else {
                progressWrap.classList.remove("active-progress");
            }
        };

        onScollEvent();
            window.onscroll = onScollEvent;
            progressWrap.onclick = function (event) {
            window.scroll({ top: 0, behavior: "smooth" });
            return false;
        };

        /*------------------------------------------------
               Preloader
        ------------------------------------------------*/
        const getPreloaderId = document.getElementById('preloader');
        if (getPreloaderId) {
            getPreloaderId.style.display = 'none';
        }
    };

    /*------------------------------------------------
               Hero Overlay On mouse move
     ------------------------------------------------*/
    window.addEventListener("load", () => {
        const spotlight = document.querySelector('.spotlight');
        if (!spotlight) return; // ✅ stop if spotlight doesn't exist

        let spotlightSize = 'transparent 20%, rgba(0, 37, 44, 0.4) 30%';

        window.addEventListener('mousemove', e => updateSpotlight(e));
        window.addEventListener('mousedown', e => {
            spotlightSize = 'transparent 10%, rgba(0, 37, 44, 0.7) 25%';
            updateSpotlight(e);
        });

        window.addEventListener('mouseup', e => {
            spotlightSize = 'transparent 20%, rgba(0, 37, 44, 0.4) 30%';
            updateSpotlight(e);
        });

        function updateSpotlight(e) {
            spotlight.style.backgroundImage = 
                `radial-gradient(circle at ${e.pageX / window.innerWidth * 100}% ${e.pageY / window.innerHeight * 100}%, ${spotlightSize}`;
        }
    });

    /*------------------------------------------------
              Hero Slider One
    ------------------------------------------------*/
    var hero_slider_one = new Swiper('.hero-slider-one', {
        spaceBetween: 24,
        speed: 1100,
        fadeEffect: { crossFade: true },
        effect: "fade",
        autoplay: {
            delay: 3500,
            disableOnInteraction: true
        },
        autoHeight: true,
        pagination: {
            el: '.hero-pagination',
            type: 'fraction',
            renderFraction: function (currentClass, totalClass) {
            return `
                <span class="${currentClass}"></span>
                <span class="swiper-separator">/</span>
                <span class="${totalClass}"></span>
            `;
            }
        },
        on: {
            slideChange: function () {
            updateFractionPadding();
            },
            init: function () {
            updateFractionPadding();
            }
        }
    });

    function updateFractionPadding() {
        const currentEl = document.querySelector('.swiper-pagination-current');
        const totalEl = document.querySelector('.swiper-pagination-total');

        if (currentEl && totalEl) {
            currentEl.textContent = currentEl.textContent.padStart(2, '0');
            totalEl.textContent = totalEl.textContent.padStart(2, '0');
        }
    }

    /*------------------------------------------------
              Testimonial Slider
    ------------------------------------------------*/
    var testimonial_slider_one = new Swiper(".testimonial-slider-one", {
        spaceBetween: 24,
        speed: 12000,
        loop:true,
        autoHeight: true,
        autoplay: {
            delay: 1,
            disableOnInteraction: true
        },
        breakpoints: {
            0: {
                slidesPerView: 1
            },
            768: {
                slidesPerView: 1.4
            },
            992: {
                slidesPerView: 2.2
            },
            1200: {
                slidesPerView: 2.8
            },
            1400: {
                slidesPerView: 3.2
            },
            1600: {
                slidesPerView: 3.5
            },
            1920: {
                slidesPerView: 4
            }
        },
    });
    var sliderContainer = document.querySelector(".testimonial-slider-one");
    if (sliderContainer && typeof testimonial_slider_one !== "undefined") {
        sliderContainer.addEventListener('mouseenter', function () {
            testimonial_slider_one.autoplay.stop();
        });
        sliderContainer.addEventListener('mouseleave', function () {
            testimonial_slider_one.autoplay.start();
        });
    }

    var testimonial_slider_two = new Swiper(".testimonial-slider-two", {
        spaceBetween: 0,
        speed: 1100,
        autoHeight: true,
        slidesPerView: 1,
        fadeEffect: { crossFade: true },
        effect: "fade",
        navigation: {
            nextEl: ".testimonial-next",
            prevEl: ".testimonial-prev",
        },
    });
    /*------------------------------------------------
              Service Slider
    ------------------------------------------------*/
	var service_slider_one = new Swiper(".service-slider-one", {
        loop: true,
        speed: 1500,
        spaceBetween: 24,
        navigation: {
            nextEl: ".service-next",
            prevEl: ".service-prev",
        },
        breakpoints: {
            0: {
                slidesPerView: 1
            },
            768: {
                slidesPerView: 2
            },
            992: {
                slidesPerView: 2.5
            },
            1200: {
                slidesPerView: 3
            },
            1300: {
                slidesPerView: 3.2
            },
            1400: {
                slidesPerView: 3.4
            },
            1600: {
                slidesPerView: 3.7
            },
            1920: {
                slidesPerView: 4
            }
        }
    });

    /*------------------------------------------------
              Case Study Slider
    ------------------------------------------------*/
	var case_slider_one = new Swiper(".case-slider-one", {
        loop: true,
        speed: 1500,
        freemode: false,
        spaceBetween: 24,
        simulateTouch: false,
         navigation: {
            nextEl: ".case-next",
            prevEl: ".case-prev",
        },
        breakpoints: {
            0: {
                slidesPerView: 1
            },
            768: {
                slidesPerView: 2
            },
            992: {
                slidesPerView: 1.3
            },
            1200: {
                slidesPerView: 1.4
            },
            1300: {
                slidesPerView: 1.5
            },
            1400: {
                slidesPerView: 1.7
            },
            1600: {
                slidesPerView: 1.9
            },
            1920: {
                slidesPerView: 2.15
            }
        }
    });

    /*------------------------------------------------
              Blog Slider
    ------------------------------------------------*/
	var blog_slider_one = new Swiper(".blog-slider-one", {
        loop: true,
        speed: 1500,
        freemode: false,
        spaceBetween: 24,
        pagination: {
            el: '.blog-pagination',
            clickable: true
        },
        breakpoints: {
            0: {
                slidesPerView: 1
            },
            768: {
                slidesPerView: 2
            },
            1200: {
                slidesPerView: 3
            }
        }
    });

    /*------------------------------------------------
             Service Js
    ------------------------------------------------*/
   




     /*------------------------------------------------
             Counter Js
    ------------------------------------------------*/
    if ("IntersectionObserver" in window) {
        let counterObserver = new IntersectionObserver(function (entries, observer) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                let counter = entry.target;
                let target = parseInt(counter.innerText);
                let step = target / 200;
                let current = 0;
                let timer = setInterval(function () {
                    current += step;
                    counter.innerText = Math.floor(current);
                    if (parseInt(counter.innerText) >= target) {
                    clearInterval(timer);
                    }
                }, 10);
                counterObserver.unobserve(counter);
                }
            });
        });
        let counters = document.querySelectorAll(".counter");
            counters.forEach(function (counter) {
            counterObserver.observe(counter);
        });
    }

   //Gsap Mousemove Animation
    /*------------------------------------------------
              Hero Slider One
    ------------------------------------------------*/
    document.addEventListener("mousemove", mouseMoveFunc);
    let moveonmouse = gsap.utils.toArray(".moveContent");

    function mouseMoveFunc(e) {
        moveonmouse.forEach((circle, index) => {
            const depth = 65;
            const moveX = (e.pageX - window.innerWidth / 2) / depth;
            const moveY = (e.pageY - window.innerHeight / 2) / depth;
            index ++

            gsap.to(circle, {
            x: moveX * index,
            y: moveY * index,
            });
        });
    }

    gsap.registerPlugin(SplitText, ScrollTrigger);
    
    
     /*------------------------------------------------
              GSAP Text Animation
    ------------------------------------------------*/
    //Text Reveal From Bottom
    document.addEventListener("DOMContentLoaded", () => {
        const titles = document.querySelectorAll(".reveal-text-bottom");

        titles.forEach((title) => {
            // Split the text into lines
            const split = new SplitText(title, { type: "lines" });
            const lines = split.lines;

            // Clear the original content
            title.innerHTML = "";

            // Wrap each line into mask > line-inner (preserve inline HTML like <span>)
            lines.forEach((line) => {
                const mask = document.createElement("div");
                mask.className = "reveal-mask";

                const inner = document.createElement("div");
                inner.className = "line-inner";
                inner.innerHTML = line.innerHTML; // ✅ preserve <span> markup

                mask.appendChild(inner);
                title.appendChild(mask);
            });

            // Animate each line on scroll
            gsap.from(title.querySelectorAll(".reveal-mask .line-inner"), {
                y: 60,
                opacity: 0,
                duration: 0.9,
                ease: "power3.out",
                stagger: 0.05,
                scrollTrigger: {
                    trigger: title,
                    start: "top 70%",
                    toggleActions: "play none play reverse",
                },
            });
        });
    });

    //Text Reveal By Letters
    let split = new SplitText(".reveal-text", { type: "chars" });

    gsap.from(split.chars, {
      x: 20,
      opacity: 0,
      duration: 0.6,
      ease: "power4.out",
      stagger: 0.05
    });

    //Text Reveal By Words
    let split_two = new SplitText(".reveal-text-two", { type: "words" });

    gsap.from(split_two.words, {
      y: 40,
      opacity: 0,
      duration: 1.2,
      ease: "power4.out",
      stagger: 0.15
    });

    //Text Reveal From Right
    window.addEventListener("load", function() {
        gsap.registerPlugin(CustomEase);
    
        // Wrap every letter
        const textRevealElements = document.querySelectorAll(".reveal-text-right");
    
        textRevealElements.forEach((element) => {
            element.innerHTML = element.textContent.replace(
                /([-A-Za-z0-9!$#%^&*@()_+|~=`{}\[\]:";'<>?,.\/]+)/g,
                '<div class="word">$1</div>'
            );
    
            let words = element.querySelectorAll(".word");
            words.forEach((word) => {
                word.innerHTML = word.textContent.replace(
                    /[-A-Za-z0-9!$#%^&*@()_+|~=`{}\[\]:";'<>?,.\/]/g,
                    "<div class='perspective'><div class='letter'><div>$&</div></div></div>"
                );
            });
    
            const letters = element.querySelectorAll(".letter");
    
            let tl = gsap.timeline({
                scrollTrigger: {
                    trigger: element,
                    toggleActions: "restart none none reset"
                }
            });
            tl.set(element, { autoAlpha: 1 });
            tl.fromTo(
                letters,
                0.6, {
                    transformOrigin: "center",
                    rotationY: 90,
                    x: 30
                }, {
                    rotationY: 0.1,
                    x: 0,
                    stagger: 0.025,
                    ease: CustomEase.create("custom", "M0,0 C0.425,0.005 0,1 1,1 ")
                }
            );
        });
    });

    //Title Animation With SplitText 
    let splitTitleLines = gsap.utils.toArray(".title-anim");

    splitTitleLines.forEach(splitTextLine => {
        const tl = gsap.timeline({
            scrollTrigger: {
            trigger: splitTextLine,
            start: 'top 90%',
            end: 'bottom 60%',
            scrub: false,
            markers: false,
            toggleActions: 'play none none none'
        }
    });

    const itemSplitted = new SplitText(splitTextLine, { type: "words, lines" });
    gsap.set(splitTextLine, { perspective: 400 });
    itemSplitted.split({ type: "lines" })
    tl.from(itemSplitted.lines, { duration: 1, delay: 0.3, opacity: 0, rotationX: -90, force3D: true, transformOrigin: "top center -50", stagger: 0.1 });
    });
    
    /*------------------------------------------------
            GSAP Image Tilt Effect
    ------------------------------------------------*/
    document.addEventListener("DOMContentLoaded", () => {
        const images = document.querySelectorAll(".tilt-img"); // Select all images

        images.forEach((img) => {
            img.addEventListener("mousemove", (e) => {
                const { width, height, left, top } = img.getBoundingClientRect();
                const x = (e.clientX - left - width / 2) / width * 2;
                const y = (e.clientY - top - height / 2) / height * 2;

                gsap.to(img, {
                    rotateY: x * 15, 
                    rotateX: y * -15, 
                    transformPerspective: 2000,
                    ease: "power2.out",
                    duration: 0.3,
                });
            });

            img.addEventListener("mouseleave", () => {
                gsap.to(img, {
                    rotateY: 0,
                    rotateX: 0,
                    duration: 0.5,
                    ease: "power2.out",
                });
            });
        });
    });

    /*------------------------------------------------
            GSAP Parallax Image With Lenis
    ------------------------------------------------*/
    const lenis = new Lenis({
        lerp: 0.09
    });

    lenis.on('scroll', ScrollTrigger.update);

        gsap.ticker.add((time)=>{
        lenis.raf(time * 1000)
    })

    gsap.utils.toArray('.img-container').forEach(container => {
    const img = container.querySelector('img');

    const tl = gsap.timeline({
        scrollTrigger: {
        trigger: container,
        scrub: true,
        pin: false,
        }
    });

    tl.fromTo(img, {
        yPercent: -20,
        ease: 'none'
        }, {
            yPercent: 20,
            ease: 'none'
        });
    });

    /*------------------------------------------------
            GSAP Move Element On Scroll
    ------------------------------------------------*/
    if (document.querySelector(".move-left")) {
        gsap.to('.move-left', {
            xPercent: 60,
            ease: "none",
            scrollTrigger: {
                trigger: ".move-left",
                start: "0% 85%",
                end: "100% 10%",
                scrub: true
            }
        });
    }
    if (document.querySelector(".move-left-two")) {
        gsap.to('.move-left-two', {
            xPercent: 15,
            ease: "none",
            scrollTrigger: {
                trigger: ".move-left-two",
                start: "0% 85%",
                end: "100% 10%",
                scrub: true
            }
        });
    }
    if (document.querySelector(".move-right")) {
        gsap.to('.move-right', {
            xPercent: -120,
            ease: "none",
            scrollTrigger: {
                trigger: ".move-right",
                start: "0% 85%",
                end: "100% 10%",
                scrub: true
            }
        });
    }
    var move_target = document.querySelector(".move-right-full");

    if (move_target) {
        const container = document.querySelector('.container-wrapper');
        const containerWidth = container?.offsetWidth || 0;
        const targetX = -containerWidth * 0.5;

        gsap.to(move_target, {
            x: targetX,
            ease: "none",
            scrollTrigger: {
                trigger: move_target,
                start: "top 85%",
                end: "bottom 10%",
                scrub: 1
            }
        });
    }

    if (document.querySelector(".move-top")) {
        gsap.to('.move-top', {
            yPercent: -40,
            ease: "none",
            scrollTrigger: {
                trigger: ".move-top",
                start: "0% 85%",
                end: "100% 10%",
                scrub: true
            }
        });
    }
    if (document.querySelector(".move-bottom")) {
        gsap.to('.move-bottom', {
            yPercent: 80,
            ease: "none",
            scrollTrigger: {
                trigger: ".move-bottom",
                start: "0% 5%",
                end: "100% 0%",
                scrub: true
            }
        });
    }
    if (document.querySelector(".move-bottom-two")) {
        gsap.to('.move-bottom-two', {
            yPercent: 20,
            ease: "none",
            scrollTrigger: {
                trigger: ".move-bottom-two",
                start: "0% 5%",
                end: "100% 0%",
                scrub: true
            }
        });
    }

    /*------------------------------------------------
              scrollCue Slider One
    ------------------------------------------------*/
    scrollCue.init({
        threshold: 0.05, 
    });

})();   

   
    /*------------------------------------------------
                Custom Cursor CSS
    ------------------------------------------------*/
    // Select cursor elements
    const cursor = document.querySelector('.cursor');
    const cursorinner = document.querySelector('.cursor-inner');
    const cursorText = document.querySelector('.cursor-text');
    const links = document.querySelectorAll('a');

    // Cursor positions
    let mouseX = 0,
        mouseY = 0;
    let posX = 0,
        posY = 0;
    const speed = 0.2; // easing factor (0.1 = slower, 0.3 = faster)

    // Track mouse position
    document.addEventListener('mousemove', e => {
        mouseX = e.clientX;
        mouseY = e.clientY;

        // Outer cursor follows instantly
        cursor.style.transform = `translate3d(calc(${mouseX}px - 50%), calc(${mouseY}px - 50%), 0)`;
    });

    // Animate inner cursor smoothly
    function animateCursor() {
        posX += (mouseX - posX) * speed;
        posY += (mouseY - posY) * speed;
        cursorinner.style.transform = `translate3d(${posX}px, ${posY}px, 0)`;
        requestAnimationFrame(animateCursor);
    }
    animateCursor();

    // -------------------
    // Click animation
    // -------------------
    document.addEventListener('mousedown', () => {
        cursor.classList.add('click');
        cursorinner.classList.add('cursorinnerhover');
    });
    document.addEventListener('mouseup', () => {
        cursor.classList.remove('click');
        cursorinner.classList.remove('cursorinnerhover');
    });

    // -------------------
    // Hover effect for links
    // -------------------
    links.forEach(link => {
        link.addEventListener('mouseenter', () => cursor.classList.add('hover'));
        link.addEventListener('mouseleave', () => cursor.classList.remove('hover'));
    });

    // -------------------
    // Custom text/icon on specific elements
    // -------------------
    const hoverTargets = document.querySelectorAll('.hover-text-target');

    hoverTargets.forEach(target => {
        target.addEventListener('mouseenter', () => {
            cursor.classList.add('show-text');
            const text = target.getAttribute('data-cursor-text');
            cursorText.textContent = text ? text : "View";
        });

        target.addEventListener('mouseleave', () => {
            cursor.classList.remove('show-text');
            cursorText.textContent = "";
        });
    });

try {

    // function to set a given theme/color-scheme
    function setTheme(themeName) {
        localStorage.setItem('exrox_theme', themeName);
        document.documentElement.className = themeName;
    }
    // function to toggle between light and dark theme
    function toggleTheme() {
        if (localStorage.getItem('exrox_theme') === 'theme-dark') {
            setTheme('theme-light');
        } else {
            setTheme('theme-dark');
        }
    }
    // Immediately invoked function to set the theme on initial load
    (function () {
        if (localStorage.getItem('exrox_theme') === 'theme-dark') {
            setTheme('theme-dark');
            document.querySelector('.slider-btn').checked = false;
        } else {
            setTheme('theme-light');
        document.querySelector('.slider-btn').checked = true;
        }
    })();

} catch (err) {}