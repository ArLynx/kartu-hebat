
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

// Registrasi plugin cukup satu kali.
gsap.registerPlugin(ScrollTrigger);

// ==========================================
// 1. AUTO DISMISS NOTIFICATION
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-auto-dismiss]').forEach((element) => {
        window.setTimeout(() => element.remove(), 5000);
    });
});

// ==========================================
// 2. SCROLL ANIMATIONS
// ==========================================
document.addEventListener('DOMContentLoaded', () => {

    // Hormati pengaturan aksesibilitas perangkat.
    const reduceMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)'
    ).matches;

    if (reduceMotion) return;

    const mm = gsap.matchMedia();

    // ==========================================
    // RESULTS PAGE - HERO ANIMATION
    // ==========================================
    const resultsHero = document.querySelector('[data-results-hero]');

    if (resultsHero) {
        const eyebrow = resultsHero.querySelector('[data-results-eyebrow]');
        const title = resultsHero.querySelector('[data-results-title]');
        const description = resultsHero.querySelector('[data-results-description]');
        const accent = resultsHero.querySelector('[data-results-accent]');

        const heroTimeline = gsap.timeline({
            defaults: {
                ease: 'power3.out',
            },
        });

        if (eyebrow) {
            heroTimeline.fromTo(
                eyebrow,
                {
                    autoAlpha: 0,
                    y: 18,
                },
                {
                    autoAlpha: 1,
                    y: 0,
                    duration: 0.75,
                }
            );
        }

        if (title) {
            heroTimeline.fromTo(
                title,
                {
                    autoAlpha: 0,
                    y: 30,
                    filter: 'blur(7px)',
                },
                {
                    autoAlpha: 1,
                    y: 0,
                    filter: 'blur(0px)',
                    duration: 1,
                },
                '-=0.4'
            );
        }

        if (description) {
            heroTimeline.fromTo(
                description,
                {
                    autoAlpha: 0,
                    y: 22,
                },
                {
                    autoAlpha: 1,
                    y: 0,
                    duration: 0.85,
                },
                '-=0.55'
            );
        }

        if (accent) {
            heroTimeline.fromTo(
                accent,
                {
                    autoAlpha: 0,
                    scaleX: 0.65,
                },
                {
                    autoAlpha: 1,
                    scaleX: 1,
                    duration: 0.7,
                    ease: 'power2.out',
                },
                '-=0.4'
            );
        }
    }

    // ==========================================
    // FOOTER ANIMATION
    // ==========================================
    const footer = document.querySelector('[data-animate-footer]');

    if (footer) {
        const footerItems = footer.querySelectorAll('[data-footer-item]');
        const footerBottomItems = footer.querySelectorAll(
            '[data-footer-bottom-item]'
        );

        const footerTimeline = gsap.timeline({
            scrollTrigger: {
                trigger: footer,
                start: 'top 88%',
                toggleActions: 'play reverse play reverse',
                invalidateOnRefresh: true,
            },
        });

        // FOOTER MAIN CONTENT
        if (footerItems.length) {
            footerTimeline.fromTo(
                footerItems,
                {
                    autoAlpha: 0,
                    y: 35,
                },
                {
                    autoAlpha: 1,
                    y: 0,
                    duration: 1,
                    stagger: 0.16,
                    ease: 'power3.out',
                }
            );
        }

        // FOOTER COPYRIGHT
        if (footerBottomItems.length) {
            footerTimeline.fromTo(
                footerBottomItems,
                {
                    autoAlpha: 0,
                    y: 18,
                },
                {
                    autoAlpha: 1,
                    y: 0,
                    duration: 0.75,
                    stagger: 0.12,
                    ease: 'power2.out',
                },
                '-=0.35'
            );
        }
    }

    // ==========================================
    // DESKTOP & TABLET
    // ==========================================
    mm.add('(min-width: 768px)', () => {

        // ------------------------------------------
        // 1. NAVBAR REVEAL
        // ------------------------------------------
        const navbar = document.querySelector('[data-animate-navbar]');

        if (navbar) {
            gsap.fromTo(
                navbar,
                { autoAlpha: 0, y: -20 },
                {
                    autoAlpha: 1,
                    y: 0,
                    duration: 1,
                    ease: 'power3.out',
                    clearProps: 'transform',
                }
            );
        }

        // ------------------------------------------
        // 2. HERO INITIAL REVEAL
        // ------------------------------------------
        const hero = document.querySelector('#hero');

        if (hero) {
            const heroTimeline = gsap.timeline({
                defaults: {
                    ease: 'power3.out',
                },
            });

            heroTimeline
                .fromTo(
                    '[data-hero-badge]',
                    { autoAlpha: 0, y: 20 },
                    { autoAlpha: 1, y: 0, duration: 0.8 }
                )
                .fromTo(
                    '[data-hero-title]',
                    { autoAlpha: 0, y: 35 },
                    { autoAlpha: 1, y: 0, duration: 1 },
                    '-=0.55'
                )
                .fromTo(
                    '[data-hero-description]',
                    { autoAlpha: 0, y: 25 },
                    { autoAlpha: 1, y: 0, duration: 0.9 },
                    '-=0.65'
                )
                .fromTo(
                    '[data-hero-actions]',
                    { autoAlpha: 0, y: 20 },
                    { autoAlpha: 1, y: 0, duration: 0.8 },
                    '-=0.55'
                )
                .fromTo(
                    '[data-hero-trust]',
                    { autoAlpha: 0, y: 15 },
                    { autoAlpha: 1, y: 0, duration: 0.8 },
                    '-=0.5'
                )
                .fromTo(
                    '[data-hero-visual]',
                    {
                        autoAlpha: 0,
                        scale: 0.95,
                        filter: 'blur(10px)',
                    },
                    {
                        autoAlpha: 1,
                        scale: 1,
                        filter: 'blur(0px)',
                        duration: 1.3,
                    },
                    '-=0.9'
                );
        }

        // ------------------------------------------
        // 3. SUBTLE HERO PARALLAX
        // ------------------------------------------
        if (hero) {
            gsap.to('[data-parallax="text"]', {
                y: -55,
                ease: 'none',
                scrollTrigger: {
                    trigger: hero,
                    start: 'top top',
                    end: 'bottom top',
                    scrub: 1.2,
                },
            });

            gsap.to('[data-parallax="glow"]', {
                y: -90,
                ease: 'none',
                scrollTrigger: {
                    trigger: hero,
                    start: 'top top',
                    end: 'bottom top',
                    scrub: 1.5,
                },
            });

            gsap.to('[data-parallax="visual"]', {
                y: -25,
                ease: 'none',
                scrollTrigger: {
                    trigger: hero,
                    start: 'top top',
                    end: 'bottom top',
                    scrub: 1.5,
                },
            });
        }

        // ------------------------------------------
        // 4. SECTION REVEAL
        // ------------------------------------------
        gsap.utils.toArray('[data-reveal]').forEach((element) => {
            gsap.fromTo(
                element,
                { autoAlpha: 0, y: 35 },
                {
                    autoAlpha: 1,
                    y: 0,
                    duration: 1,
                    ease: 'power3.out',
                    scrollTrigger: {
                        trigger: element,
                        start: 'top 85%',
                        toggleActions: 'play reverse play reverse',
                        invalidateOnRefresh: true,
                    },
                }
            );
        });

        // ------------------------------------------
        // 5. STAGGER GRID CARDS
        // ------------------------------------------
        gsap.utils.toArray('[data-stagger]').forEach((grid) => {
            const cards = grid.querySelectorAll('[data-stagger-card]');

            if (!cards.length) return;

            gsap.fromTo(
                cards,
                { autoAlpha: 0, y: 32 },
                {
                    autoAlpha: 1,
                    y: 0,
                    duration: 0.9,
                    stagger: 0.08,
                    ease: 'power3.out',
                    scrollTrigger: {
                        trigger: grid,
                        start: 'top 82%',
                        toggleActions: 'play reverse play reverse',
                        invalidateOnRefresh: true,
                    },
                }
            );
        });

        ScrollTrigger.refresh();
    });

    // ==========================================
    // MOBILE
    // ==========================================
    mm.add('(max-width: 767px)', () => {

        // ------------------------------------------
        // 1. SECTION REVEAL
        // ------------------------------------------
        gsap.utils.toArray('[data-reveal]').forEach((element) => {
            gsap.fromTo(
                element,
                { autoAlpha: 0, y: 24 },
                {
                    autoAlpha: 1,
                    y: 0,
                    duration: 0.8,
                    ease: 'power3.out',
                    scrollTrigger: {
                        trigger: element,
                        start: 'top 88%',
                        toggleActions: 'play reverse play reverse',
                    },
                }
            );
        });

        // ------------------------------------------
        // 2. STAGGER GRID CARDS
        // ------------------------------------------
        gsap.utils.toArray('[data-stagger]').forEach((grid) => {
            const cards = grid.querySelectorAll('[data-stagger-card]');

            if (!cards.length) return;

            gsap.fromTo(
                cards,
                { autoAlpha: 0, y: 24 },
                {
                    autoAlpha: 1,
                    y: 0,
                    duration: 0.75,
                    stagger: 0.07,
                    ease: 'power3.out',
                    scrollTrigger: {
                        trigger: grid,
                        start: 'top 88%',
                        toggleActions: 'play reverse play reverse',
                    },
                }
            );
        });

        ScrollTrigger.refresh();
    });
});
