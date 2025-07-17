<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SecureConnect Kenya | Security Management System</title>
    <meta name="description" content="Comprehensive security management system for organizations, guards, and administrators in Kenya">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        :root {
            --primary: #1a237e;
            --secondary: #ffca28;
            --accent: #0d47a1;
            --text: #333333;
            --light-text: #666666;
            --background: #ffffff;
            --light-bg: #f5f5f5;
            --border: #e0e0e0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text);
            background-color: var(--background);
            line-height: 1.6;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        header {
            background-color: rgba(26, 35, 126, 0.9);
            color: white;
            padding: 15px 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(5px);
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
        }

        .logo span:first-child {
            color: #4CAF50; /* A touch of green for 'Secure' */
        }

        .logo span:last-child {
            color: var(--secondary);
        }

        .logo i {
            margin-right: 10px;
            color: var(--secondary);
        }

        nav ul {
            display: flex;
            list-style: none;
        }

        nav ul li {
            margin-left: 30px;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
            position: relative;
        }

        nav ul li a:hover {
            color: var(--secondary);
        }

        nav ul li a.active {
            color: var(--secondary);
        }

        nav ul li a.active:after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: var(--secondary);
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Hero Section with Background Image Carousel */
        .hero {
            padding: 180px 0 100px;
            text-align: center;
            position: relative;
            color: white;
            overflow: hidden; /* Important for sliding images */
            height: 100vh; /* Make hero section full viewport height */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-background-carousel {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1; /* Send behind content */
        }

        .hero-background-carousel .background-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center center;
            opacity: 0;
            transition: opacity 1.5s ease-in-out; /* Smooth fade effect */
        }

        .hero-background-carousel .background-image.active {
            opacity: 1;
        }

        .hero:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6); /* Dark overlay */
            z-index: 0;
        }

        .hero .container {
            position: relative;
            z-index: 1; /* Bring content to front */
        }

        .hero h1 {
            font-size: 3.2rem; /* Slightly larger heading */
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.5); /* Stronger text shadow */
        }

        .hero p {
            font-size: 1.4rem; /* Slightly larger text */
            max-width: 800px;
            margin: 0 auto 40px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.4);
        }

        /* Button Styles */
        .btn {
            display: inline-block;
            padding: 14px 35px; /* Larger buttons */
            background-color: var(--secondary);
            color: var(--primary);
            border: none;
            border-radius: 50px; /* Pill-shaped buttons */
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            font-size: 1.1rem;
        }

        .btn:hover {
            background-color: #ffd54f;
            transform: translateY(-3px); /* More pronounced hover effect */
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.2);
        }

        .btn-secondary {
            background-color: transparent;
            color: white;
            border: 2px solid white;
            margin-left: 20px; /* More spacing */
        }

        .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.2);
            border-color: var(--secondary); /* Border changes on hover */
        }

        /* Features Section with Subtle Pattern */
        .section {
            padding: 80px 0;
        }

        .features-section {
            background-color: var(--light-bg);
            background-image: radial-gradient(circle at 10% 20%, rgba(0, 0, 0, 0.01) 0%, rgba(0, 0, 0, 0.01) 90%);
        }

        .section-title {
            text-align: center;
            margin-bottom: 60px; /* More space below title */
        }

        .section-title h2 {
            font-size: 2.5rem; /* Larger title */
            color: var(--primary);
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }

        .section-title h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background-color: var(--secondary);
            border-radius: 2px;
        }

        .section-title p {
            color: var(--light-text);
            max-width: 700px;
            margin: 0 auto;
            font-size: 1.1rem;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .feature-card {
            background: white;
            border-radius: 10px; /* More rounded corners */
            padding: 30px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08); /* Stronger shadow */
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
        }

        .feature-card:hover {
            transform: translateY(-15px); /* More pronounced hover effect */
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .feature-icon {
            font-size: 3rem; /* Larger icons */
            color: var(--secondary);
            margin-bottom: 25px; /* More space below icon */
        }

        .feature-card h3 {
            font-size: 1.4rem; /* Larger feature titles */
            margin-bottom: 15px;
            color: var(--primary);
        }

        .feature-card p {
            font-size: 1rem;
            color: var(--light-text);
        }

        /* About Section with Image */
        .about {
            position: relative;
            overflow: hidden;
            background-color: var(--background);
        }

        .about-image {
            background: url('https://plus.unsplash.com/premium_photo-1676618539992-21c7d3b6df0f?q=80&w=1032&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3Dc') no-repeat center center;
            background-size: cover;
            min-height: 400px; /* Ensure image section has height */
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px; /* More gap */
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .about-text {
            padding: 0; /* Remove padding here as card will have it */
            border-radius: 8px;
        }

        .about-text h2 {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 20px;
        }

        .about-text p {
            margin-bottom: 20px;
            color: var(--light-text);
            font-size: 1.05rem;
        }

        /* Contact Section */
        .contact {
            background: linear-gradient(rgba(26, 35, 126, 0.9), rgba(26, 35, 126, 0.9)),
                        url('https://images.unsplash.com/photo-1557804506-669a67965ba0?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80') no-repeat center center;
            background-size: cover;
            color: white;
            padding: 100px 0; /* More padding */
        }

        .contact .section-title h2,
        .contact .section-title p {
            color: white;
        }

        .contact .section-title h2::after {
            background-color: var(--secondary);
        }

        .contact-form {
            background: rgba(255, 255, 255, 0.15); /* Slightly less transparent */
            padding: 50px; /* More padding */
            border-radius: 10px; /* More rounded corners */
            backdrop-filter: blur(8px); /* Stronger blur */
            max-width: 700px; /* Wider form */
            margin: 0 auto;
            border: 1px solid rgba(255, 255, 255, 0.3); /* Stronger border */
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .form-group {
            margin-bottom: 25px; /* More spacing */
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600; /* Bolder labels */
            font-size: 1.1rem;
        }

        .form-control {
            width: 100%;
            padding: 15px 18px; /* Larger input fields */
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            transition: border-color 0.3s, background-color 0.3s;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.8);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--secondary);
            background: rgba(255, 255, 255, 0.25);
            box-shadow: 0 0 0 3px rgba(255, 202, 40, 0.3); /* Focus glow */
        }

        textarea.form-control {
            min-height: 180px; /* Taller textarea */
            resize: vertical;
        }

        /* Footer */
        footer {
            background-color: var(--primary);
            color: white;
            padding: 60px 0 30px; /* More padding */
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); /* Slightly wider columns */
            gap: 40px; /* More gap */
            margin-bottom: 40px;
        }

        .footer-column h3 {
            font-size: 1.3rem; /* Larger footer headings */
            margin-bottom: 25px;
            color: var(--secondary);
            position: relative;
        }

        .footer-column h3::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: var(--secondary);
            border-radius: 2px;
        }

        .footer-column ul {
            list-style: none;
        }

        .footer-column ul li {
            margin-bottom: 12px; /* More spacing */
        }

        .footer-column ul li a {
            color: #e0e0e0;
            text-decoration: none;
            transition: color 0.3s;
            font-size: 0.95rem;
        }

        .footer-column ul li a:hover {
            color: var(--secondary);
            text-decoration: underline;
        }

        .footer-column ul li i {
            margin-right: 10px;
            color: var(--secondary);
        }

        .social-links {
            display: flex;
            gap: 20px; /* More spacing */
            margin-top: 20px;
        }

        .social-links a {
            color: white;
            font-size: 1.5rem; /* Larger social icons */
            transition: color 0.3s, transform 0.3s;
        }

        .social-links a:hover {
            color: var(--secondary);
            transform: translateY(-3px);
        }

        .copyright {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            color: #e0e0e0;
            font-size: 0.95rem;
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .hero h1 {
                font-size: 2.8rem;
            }
            .hero p {
                font-size: 1.2rem;
            }
            .about-content {
                grid-template-columns: 1fr;
            }

            .about-image {
                min-height: 300px;
                margin-bottom: 40px;
            }
        }

        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }

            nav {
                position: fixed;
                top: 70px;
                left: -100%;
                width: 80%;
                height: calc(100vh - 70px);
                background-color: rgba(26, 35, 126, 0.98); /* Slightly more opaque */
                transition: left 0.4s ease;
                padding: 20px;
                box-shadow: 2px 0 15px rgba(0, 0, 0, 0.2);
                backdrop-filter: blur(8px); /* Stronger blur */
            }

            nav.active {
                left: 0;
            }

            nav ul {
                flex-direction: column;
            }

            nav ul li {
                margin: 20px 0; /* More spacing */
            }

            nav ul li a {
                font-size: 1.2rem;
            }

            .hero {
                padding: 150px 0 80px;
                height: auto; /* Allow height to adjust */
            }

            .hero h1 {
                font-size: 2.2rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            .btn {
                display: block;
                width: fit-content; /* Adjust width to content */
                margin: 15px auto; /* Center buttons */
                padding: 12px 25px;
            }

            .btn-secondary {
                margin-left: auto;
                margin-right: auto;
            }

            .section {
                padding: 60px 0;
            }
            .section-title h2 {
                font-size: 2rem;
            }
            .features {
                grid-template-columns: 1fr;
            }

            .contact-form {
                padding: 40px 25px;
            }

            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .footer-column h3::after {
                left: 50%;
                transform: translateX(-50%);
            }

            .social-links {
                justify-content: center;
            }
        }

        @media (max-width: 576px) {
            .hero {
                padding: 120px 0 60px;
            }

            .hero h1 {
                font-size: 1.8rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .btn {
                padding: 10px 20px;
                font-size: 0.9rem;
            }

            .header-container {
                padding: 0 15px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-container">
            <a href="#" class="logo">
                <i class="fas fa-shield-alt"></i>
                <span>Secure</span><span>Connect</span>
            </a>

            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>

            <nav id="mainNav">
                <ul>
                    <li><a href="#home" class="active">Home</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#about">About</a></li>
                    <li><a href="#contact">Contact</a></li>
                    <li><a href="page.php" class="btn btn-secondary">Login</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="hero" id="home">
        <div class="hero-background-carousel" id="heroCarousel">
            </div>
        <div class="container">
            <h1>Security Management <span>Simplified</span></h1>
            <p>Your trusted security management system for organizations, guards, and administrators in Kenya. Manage incidents, guard assignments, analytics, and more with ease.</p>
            <div>
                <a href="#contact" class="btn">Request Demo</a>
                <a href="page.php" class="btn btn-secondary">Get Started</a>
            </div>
        </div>
    </section>

    <section class="section features-section" id="features">
        <div class="container">
            <div class="section-title">
                <h2>Key Features</h2>
                <p>Discover how SecureConnect Kenya can transform your security operations</p>
            </div>

            <div class="features">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <h3>Automated Duty Management</h3>
                    <p>Replace manual scheduling with our automated system that optimizes guard assignments and reduces human error.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3>Real-time Communication</h3>
                    <p>Enhances communication between security firms and their clients with instant messaging and notifications.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h3>Guard Deployment</h3>
                    <p>Organizations can view, request, and adjust guard deployment through an intuitive interface.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3>Real-time Tracking</h3>
                    <p>Improves visibility and accountability with GPS-based tracking of security personnel.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Advanced Analytics</h3>
                    <p>Gain insights into security operations with comprehensive reporting and data visualization tools.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3>Incident Management</h3>
                    <p>Quickly report and respond to security incidents with our streamlined workflow system.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section about" id="about">
        <div class="container">
            <div class="about-content">
                <div class="about-image"></div> <div class="about-text">
                    <h2>About SecureConnect Kenya</h2>
                    <p>SecureConnect Kenya is a comprehensive security management system designed to streamline operations for organizations, guards, and administrators. Our platform enhances communication, scheduling, and analytics to improve security outcomes across Kenya.</p>
                    <p>Founded in 2023, we've been at the forefront of digital security solutions, helping businesses of all sizes manage their security needs more efficiently and effectively.</p>
                    <a href="#contact" class="btn">Learn More</a>
                </div>
            </div>
        </div>
    </section>

    <section class="section contact" id="contact">
        <div class="container">
            <div class="section-title">
                <h2>Contact Us</h2>
                <p>Get in touch to learn more about how SecureConnect can benefit your organization</p>
            </div>

            <div class="contact-form">
                <form id="contactForm" action="contact_submit.php" method="POST">
                    <div class="form-group">
                        <label for="name">Your Name</label>
                        <input type="text" id="name" name="name" class="form-control" required placeholder="John Doe">
                    </div>

                    <div class="form-group">
                        <label for="email">Your Email</label>
                        <input type="email" id="email" name="email" class="form-control" required placeholder="john@example.com">
                    </div>

                    <div class="form-group">
                        <label for="message">Your Message</label>
                        <textarea id="message" name="message" class="form-control" required placeholder="Tell us about your security needs..."></textarea>
                    </div>

                    <button type="submit" class="btn">Send Message</button>
                </form>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>SecureConnect</h3>
                    <p>Your trusted security management partner in Kenya.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>

                <div class="footer-column">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="#home">Home</a></li>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="#contact">Contact</a></li>
                        <li><a href="page.php">Login</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h3>Services</h3>
                    <ul>
                        <li><a href="#">Guard Management</a></li>
                        <li><a href="#">Incident Reporting</a></li>
                        <li><a href="#">Security Analytics</a></li>
                        <li><a href="#">Training</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h3>Contact Info</h3>
                    <ul>
                        <li><i class="fas fa-map-marker-alt"></i> Nairobi, Kenya</li>
                        <li><i class="fas fa-phone"></i> +254 700 000000</li>
                        <li><i class="fas fa-envelope"></i> info@secureconnect.co.ke</li>
                    </ul>
                </div>
            </div>

            <div class="copyright">
                <p>&copy; 2024 SecureConnect Kenya. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile Menu Toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mainNav = document.getElementById('mainNav');

        mobileMenuBtn.addEventListener('click', () => {
            mainNav.classList.toggle('active');
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();

                // Close mobile menu if open
                mainNav.classList.remove('active');

                const targetId = this.getAttribute('href');
                if (targetId === '#') return;

                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 70, // Adjust for fixed header
                        behavior: 'smooth'
                    });

                    // Update active nav link
                    document.querySelectorAll('nav a').forEach(link => {
                        link.classList.remove('active');
                    });
                    this.classList.add('active');
                }
            });
        });

        // Change active nav link on scroll
        window.addEventListener('scroll', () => {
            const scrollPosition = window.scrollY;

            document.querySelectorAll('section').forEach(section => {
                const sectionTop = section.offsetTop - 100; // Offset for header height
                const sectionBottom = sectionTop + section.offsetHeight;
                const sectionId = section.getAttribute('id');

                if (scrollPosition >= sectionTop && scrollPosition < sectionBottom) {
                    document.querySelectorAll('nav a').forEach(link => {
                        link.classList.remove('active');
                        if (link.getAttribute('href') === #${sectionId}) {
                            link.classList.add('active');
                        }
                    });
                }
            });
        });

        // Image Carousel for Hero Section
        const heroCarousel = document.getElementById('heroCarousel');
        const images = [
            'https://images.unsplash.com/photo-1605106702734-205df224ecce?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80',
            'https://images.unsplash.com/photo-1605106702734-205df224ecce?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80',
            'https://images.unsplash.com/photo-1605106702734-205df224ecce?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80',
            'https://images.unsplash.com/photo-1605106702734-205df224ecce?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80'
        ];
        let currentImageIndex = 0;

        function changeHeroBackground() {
            // Remove active class from all images
            Array.from(heroCarousel.children).forEach(img => img.classList.remove('active'));

            // Get the current image element or create a new one
            let currentImageElement = heroCarousel.querySelector(.background-image[data-index="${currentImageIndex}"]);

            if (!currentImageElement) {
                currentImageElement = document.createElement('div');
                currentImageElement.classList.add('background-image');
                currentImageElement.setAttribute('data-index', currentImageIndex);
                heroCarousel.appendChild(currentImageElement);
            }

            currentImageElement.style.backgroundImage = url('${images[currentImageIndex]}');
            currentImageElement.classList.add('active');

            currentImageIndex = (currentImageIndex + 1) % images.length;
        }

        // Initialize carousel
        changeHeroBackground(); // Show the first image immediately
        setInterval(changeHeroBackground, 5000); // Change image every 5 seconds (5000ms)

    </script>
</body>
</html>