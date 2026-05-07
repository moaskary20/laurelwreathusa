const body = document.body;
const themeToggle = document.getElementById('themeToggle');
const searchInput = document.getElementById('guideSearch');
const sections = Array.from(document.querySelectorAll('.guide-section'));
const tocLinks = Array.from(document.querySelectorAll('.toc a'));
const backToTop = document.getElementById('backToTop');
const content = document.getElementById('guideContent');

const savedTheme = localStorage.getItem('userGuideTheme');
if (savedTheme === 'dark') {
    body.classList.add('dark');
}

themeToggle?.addEventListener('click', () => {
    body.classList.toggle('dark');
    localStorage.setItem('userGuideTheme', body.classList.contains('dark') ? 'dark' : 'light');
});

function normalizeArabic(value) {
    return value
        .toLowerCase()
        .replace(/[أإآ]/g, 'ا')
        .replace(/ة/g, 'ه')
        .replace(/ى/g, 'ي')
        .replace(/[ًٌٍَُِّْ]/g, '')
        .trim();
}

function applySearch() {
    const query = normalizeArabic(searchInput.value || '');
    let visibleCount = 0;
    document.getElementById('searchEmpty')?.remove();

    sections.forEach((section) => {
        const haystack = normalizeArabic(section.textContent || '');
        const isVisible = query === '' || haystack.includes(query);
        section.classList.toggle('hidden-by-search', !isVisible);
        if (isVisible) {
            visibleCount += 1;
        }
    });

    if (visibleCount === 0) {
        const empty = document.createElement('div');
        empty.id = 'searchEmpty';
        empty.className = 'search-empty';
        empty.textContent = 'لا توجد نتائج مطابقة للبحث الحالي.';
        content.prepend(empty);
    }
}

searchInput?.addEventListener('input', applySearch);

const observer = new IntersectionObserver((entries) => {
    const visible = entries
        .filter((entry) => entry.isIntersecting)
        .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];

    if (!visible) {
        return;
    }

    tocLinks.forEach((link) => {
        link.classList.toggle('active', link.getAttribute('href') === `#${visible.target.id}`);
    });
}, {
    rootMargin: '-20% 0px -65% 0px',
    threshold: [0.1, 0.25, 0.5],
});

sections.forEach((section) => observer.observe(section));

window.addEventListener('scroll', () => {
    backToTop?.classList.toggle('visible', window.scrollY > 700);
});

backToTop?.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
});
