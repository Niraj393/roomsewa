
document.addEventListener('DOMContentLoaded', function () {
  const menuToggle = document.getElementById('menuToggle');
  const mainNav = document.getElementById('mainNav');
  const overlay = document.getElementById('overlay');
  const mainContent = document.querySelector('.main-content'); 

  menuToggle.addEventListener('click', function () {
    mainNav.classList.toggle('active');
    overlay.classList.toggle('active');
    mainContent.classList.toggle('shifted');
this.textContent = mainNav.classList.contains('active') ? '✕' : '☰';
    });
     // Close when overlay clicked
  overlay.addEventListener('click', function () {
    mainNav.classList.remove('active');
    overlay.classList.remove('active');
    mainContent.classList.remove('shifted');
    menuToggle.textContent = '☰';
  });

  // Close when nav link clicked
  document.querySelectorAll('#mainNav a').forEach(link => {
    link.addEventListener('click', function () {
      mainNav.classList.remove('active');
      overlay.classList.remove('active');
       mainContent.classList.remove('shifted');
      menuToggle.textContent = '☰';
    });
  });
});

// Hero text animation
const heroTexts = document.querySelectorAll('.hero-text');
let currentHero = 0;

function rotateHeroText() {
  heroTexts.forEach(text => text.classList.remove('active'));
  heroTexts[currentHero].classList.add('active');
  currentHero = (currentHero + 1) % heroTexts.length;
  setTimeout(rotateHeroText, 3000);
}
setTimeout(rotateHeroText, 2000);

// Feature cards animation
const featureCards = document.querySelectorAll('.feature-card');
const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('show');
    }
  });
}, { threshold: 0.1 });

featureCards.forEach(card => observer.observe(card));
