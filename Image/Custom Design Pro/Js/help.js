document.addEventListener('DOMContentLoaded', () => {
  const helpButton = document.querySelector('[data-tool="help"]');
  const modal = document.getElementById('cdpHelpModal');
  if (!helpButton || !modal) return;

  const chapterListEl = document.getElementById('cdpHelpChapters');
  const videoEl = document.getElementById('cdpHelpVideo');
  const videoWrapperEl = videoEl ? videoEl.parentElement : null;
  const titleEl = document.getElementById('cdpHelpVideoTitle');
  const descEl = document.getElementById('cdpHelpVideoDesc');
  const pillEl = document.getElementById('cdpHelpChapterLabel');
  const closeBtn = document.getElementById('cdpHelpClose');
  const backdrop = modal.querySelector('[data-help-dismiss]');
  const trackButtons = document.querySelectorAll('[data-help-track]');

  const trackLabels = {
    basic: 'Basic Tutorials',
    pro: 'Pro Tutorials'
  };
  const YOUTUBE_CHANNEL_URL = 'https://www.youtube.com/channel/UCjNp18BqkwnKljgsPERv6tw';
  let externalVideoMessageEl = null;

  function ensureExternalVideoMessage() {
    if (!videoWrapperEl) return null;
    if (externalVideoMessageEl) return externalVideoMessageEl;

    externalVideoMessageEl = document.createElement('div');
    externalVideoMessageEl.className = 'cdp-help-external-message';
    externalVideoMessageEl.style.display = 'none';
    externalVideoMessageEl.style.width = '100%';
    externalVideoMessageEl.style.height = '100%';
    externalVideoMessageEl.style.minHeight = '260px';
    externalVideoMessageEl.style.color = '#fff';
    externalVideoMessageEl.style.padding = '24px';
    externalVideoMessageEl.style.boxSizing = 'border-box';
    externalVideoMessageEl.style.display = 'none';
    externalVideoMessageEl.style.flexDirection = 'column';
    externalVideoMessageEl.style.justifyContent = 'center';
    externalVideoMessageEl.style.alignItems = 'center';
    externalVideoMessageEl.style.gap = '12px';
    externalVideoMessageEl.style.textAlign = 'center';
    videoWrapperEl.appendChild(externalVideoMessageEl);
    return externalVideoMessageEl;
  }

  function buildYouTubeChapter(chapterNumber, title, description) {
    const query = encodeURIComponent(`GirffoN Studio Chapter ${chapterNumber}`);
    return {
      title,
      description,
      youtube: `https://www.youtube.com/results?search_query=${query}`,
      youtubeEmbed: `https://www.youtube.com/embed?listType=search&list=${query}`
    };
  }

  const tutorialTracks = {
    basic: [
      {
        title: 'Introduction',
        description: 'Custom T-Shirt Design Tutorial | Chapter 1 Introduction | Beginner Guide.',
        youtube: 'https://www.youtube.com/watch?v=LDgtyHzYQFY',
        youtubeEmbed: 'https://www.youtube.com/embed/LDgtyHzYQFY'
      },
      {
        title: 'How to Use Products (Step-by-Step)',
        description: 'Custom T-Shirt Design Tutorial | Chapter 2',
        youtube: 'https://www.youtube.com/watch?v=aY51McNqDL4',
        youtubeEmbed: 'https://www.youtube.com/embed/aY51McNqDL4'
      },
      {
        title: 'File & Settings Explained',
        description: 'Custom T-Shirt Design Tutorial | Chapter 3',
        youtube: 'https://www.youtube.com/watch?v=w9FIt6clbVw',
        youtubeEmbed: 'https://www.youtube.com/embed/w9FIt6clbVw'
      },
      {
        title: 'Fill Color Tool Tutorial',
        description: 'Custom T-Shirt Design | Chapter 4',
        youtube: 'https://www.youtube.com/watch?v=H9Yfk6t_hHc',
        youtubeEmbed: 'https://www.youtube.com/embed/H9Yfk6t_hHc'
      },
      {
        title: 'Add Text Tool Tutorial',
        description: 'Custom T-Shirt Design | Chapter 5',
        youtube: 'https://www.youtube.com/watch?v=rX0U0Osghs0',
        youtubeEmbed: 'https://www.youtube.com/embed/rX0U0Osghs0'
      },
      {
        title: 'How to Use Icons',
        description: 'Custom T-Shirt Design Tutorial | Chapter 6',
        youtube: 'https://www.youtube.com/watch?v=Bd1UkjF4qGQ',
        youtubeEmbed: 'https://www.youtube.com/embed/Bd1UkjF4qGQ'
      },
      {
        title: 'How to Add Flags',
        description: 'Custom T-Shirt Design Tutorial | Chapter 7',
        youtube: 'https://www.youtube.com/watch?v=6TxR7yZLQqY',
        youtubeEmbed: 'https://www.youtube.com/embed/6TxR7yZLQqY'
      },
      {
        title: 'How to Use Shapes',
        description: 'Custom T-Shirt Design Tutorial | Chapter 8',
        youtube: 'https://www.youtube.com/watch?v=q1_kB3BZ6Yo',
        youtubeEmbed: 'https://www.youtube.com/embed/q1_kB3BZ6Yo'
      },
      {
        title: 'How to Add Design Images',
        description: 'Custom T-Shirt Design | Chapter 9',
        youtube: 'https://www.youtube.com/watch?v=qdXK13gTSr0',
        youtubeEmbed: 'https://www.youtube.com/embed/qdXK13gTSr0'
      },
      {
        title: 'Chapter 10: Upload',
        description: 'Custom T-Shirt Design Tutorial | Chapter 10',
        youtube: 'https://www.youtube.com/watch?v=IUvGirbFNFU',
        youtubeEmbed: 'https://www.youtube.com/embed/IUvGirbFNFU'
      },
      {
        title: 'How to Use Erase Tool',
        description: 'Custom T-Shirt Design | Chapter 11',
        youtube: 'https://www.youtube.com/watch?v=sPXy7_DpHnM',
        youtubeEmbed: 'https://www.youtube.com/embed/sPXy7_DpHnM'
      },
      {
        title: 'Chapter 12: Filters',
        description: 'Custom T-Shirt Design Tutorial | Chapter 12',
        youtube: 'https://www.youtube.com/watch?v=DqAg_3SPMVA',
        youtubeEmbed: 'https://www.youtube.com/embed/DqAg_3SPMVA'
      },
      {
        title: 'Chapter 13: Cart & Final Order',
        description: 'Custom T-Shirt Design Tutorial | Chapter 13',
        youtube: 'https://www.youtube.com/watch?v=d6fYzNGZpFo',
        youtubeEmbed: 'https://www.youtube.com/embed/d6fYzNGZpFo'
      }
    ],
    pro: [
      {
        title: 'Advanced Design Composition - Part 1',
        description: 'Custom T-Shirt Design Pro | Advanced Design Composition - Part 1',
        youtube: 'https://www.youtube.com/watch?v=gy7zRDfpHYo&t=17s',
        youtubeEmbed: 'https://www.youtube.com/embed/gy7zRDfpHYo?start=17'
      },
      {
        title: 'Advanced Design Composition - Part 2',
        description: 'Custom T-Shirt Design Pro | Advanced Design Composition - Part 2',
        youtube: 'https://www.youtube.com/watch?v=Rnc1_PV-iC8&t=16s',
        youtubeEmbed: 'https://www.youtube.com/embed/Rnc1_PV-iC8?start=16'
      },
      {
        title: 'Advanced Design Composition - Part 3',
        description: 'Custom T-Shirt Design Pro | Advanced Design Composition - Part 3',
        youtube: 'https://www.youtube.com/watch?v=9QS-yFkknFM&t=13s',
        youtubeEmbed: 'https://www.youtube.com/embed/9QS-yFkknFM?start=13'
      },
      {
        title: 'Advanced Design Composition - Part 4',
        description: 'Custom T-Shirt Design Pro | Advanced Design Composition - Part 4',
        youtube: 'https://www.youtube.com/watch?v=FmMGed0zLis&t=22s',
        youtubeEmbed: 'https://www.youtube.com/embed/FmMGed0zLis?start=22'
      },
      {
        title: 'Advanced Design Composition - Part 5',
        description: 'Custom T-Shirt Design Pro | Advanced Design Composition - Part 5',
        youtube: 'https://www.youtube.com/watch?v=4gldO2HIyIg&t=10s',
        youtubeEmbed: 'https://www.youtube.com/embed/4gldO2HIyIg?start=10'
      },
      {
        title: 'Advanced Design Composition - Part 6',
        description: 'Custom T-Shirt Design Pro | Advanced Design Composition - Part 6',
        youtube: 'https://www.youtube.com/watch?v=h9gFCgKw8ao&t=0s',
        youtubeEmbed: 'https://www.youtube.com/embed/h9gFCgKw8ao?start=0'
      }
      
    ]
  };

  let currentTrack = 'basic';
  let currentChapterIndex = 0;
  let youtubeFrame = null;

  function ensureYouTubeFrame() {
    if (!videoWrapperEl) return null;
    if (youtubeFrame) return youtubeFrame;

    youtubeFrame = document.createElement('iframe');
    youtubeFrame.className = 'cdp-help-video cdp-help-video--youtube';
    youtubeFrame.style.display = 'none';
    youtubeFrame.setAttribute('allowfullscreen', '');
    youtubeFrame.setAttribute('title', 'YouTube Tutorial');
    youtubeFrame.setAttribute('loading', 'lazy');
    youtubeFrame.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
    youtubeFrame.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
    videoWrapperEl.appendChild(youtubeFrame);
    return youtubeFrame;
  }

  function getActiveChapters() {
    return tutorialTracks[currentTrack] || [];
  }

  function setVideoSource(chapter) {
    if (!chapter) return;
    const frame = ensureYouTubeFrame();
    const externalMessage = ensureExternalVideoMessage();

    if (chapter.youtubeEmbed && frame) {
      if (videoEl) {
        videoEl.pause();
        videoEl.style.display = 'none';
        if (videoEl.getAttribute('src')) {
          videoEl.removeAttribute('src');
          videoEl.load();
        }
      }

      const isSameFrameVideo = frame.dataset.currentSrc === chapter.youtubeEmbed;
      if (!isSameFrameVideo) {
        frame.setAttribute('src', chapter.youtubeEmbed);
        frame.dataset.currentSrc = chapter.youtubeEmbed;
      }
      frame.style.display = 'block';

      if (externalMessage) {
        externalMessage.style.display = 'none';
        externalMessage.innerHTML = '';
      }
      return;
    }

    if (chapter.youtube) {
      if (videoEl) {
        videoEl.pause();
        videoEl.style.display = 'none';
        if (videoEl.getAttribute('src')) {
          videoEl.removeAttribute('src');
          videoEl.load();
        }
      }

      if (frame) {
        frame.style.display = 'none';
        if (frame.getAttribute('src')) {
          frame.setAttribute('src', '');
        }
        frame.dataset.currentSrc = '';
      }

      if (externalMessage) {
        externalMessage.innerHTML = `
          <h4 style="margin:0;font-size:20px;">Video is available on YouTube</h4>
          <p style="margin:0;opacity:0.85;max-width:520px;">Some YouTube videos cannot play inside embedded players. Click below to watch this chapter directly on YouTube.</p>
          <a href="${chapter.youtube || YOUTUBE_CHANNEL_URL}" target="_blank" rel="noopener noreferrer" style="display:inline-flex;align-items:center;gap:8px;background:#d9a300;color:#111827;font-weight:700;text-decoration:none;padding:10px 16px;border-radius:999px;">
            Open Chapter on YouTube
          </a>
        `;
        externalMessage.style.display = 'flex';
      }
      return;
    }

    if (externalMessage) {
      externalMessage.style.display = 'none';
      externalMessage.innerHTML = '';
    }

    if (frame) {
      frame.style.display = 'none';
      if (frame.getAttribute('src')) {
        frame.setAttribute('src', '');
      }
      frame.dataset.currentSrc = '';
    }

    if (!videoEl || !chapter.video) return;
    videoEl.style.display = 'block';
    const source = chapter.video;
    const isSameVideo = videoEl.dataset.currentSrc === source;
    if (!isSameVideo) {
      videoEl.pause();
      videoEl.setAttribute('src', source);
      videoEl.load();
      videoEl.dataset.currentSrc = source;
    }
  }

  function renderChapterButtons() {
    if (!chapterListEl) return;
    const activeChapters = getActiveChapters();
    chapterListEl.innerHTML = '';

    if (!activeChapters.length) {
      chapterListEl.innerHTML = '<p class="cdp-help-empty">More tutorials are on the way.</p>';
      return;
    }

    activeChapters.forEach((chapter, index) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'cdp-help-chapter';
      if (index === currentChapterIndex) {
        button.classList.add('active');
      }

      button.innerHTML = `
        <div>
          <strong>Chapter ${index + 1}: ${chapter.title}</strong>
          <span>${chapter.description}</span>
        </div>
        <i class="fa-solid ${(chapter.youtube && !chapter.youtubeEmbed) ? 'fa-arrow-up-right-from-square' : 'fa-play'}"></i>
      `;

      button.addEventListener('click', () => {
        selectChapter(index);
        if (chapter.youtube && !chapter.youtubeEmbed) {
          window.open(chapter.youtube, '_blank', 'noopener,noreferrer');
        }
      });
      chapterListEl.appendChild(button);
    });
  }

  function updateChapterUI() {
    const activeChapters = getActiveChapters();
    const chapter = activeChapters[currentChapterIndex];
    if (!chapter) return;

    setVideoSource(chapter);
    if (titleEl) titleEl.textContent = chapter.title;
    if (descEl) descEl.textContent = chapter.description;
    if (pillEl) {
      const label = trackLabels[currentTrack] || 'Tutorial';
      pillEl.textContent = `${label} · Chapter ${currentChapterIndex + 1}`;
    }

    if (chapterListEl) {
      const buttons = chapterListEl.querySelectorAll('.cdp-help-chapter');
      buttons.forEach((btn, idx) => {
        btn.classList.toggle('active', idx === currentChapterIndex);
      });
    }
  }

  function selectChapter(index) {
    const activeChapters = getActiveChapters();
    if (!activeChapters.length) return;
    const safeIndex = Math.min(Math.max(index, 0), activeChapters.length - 1);
    currentChapterIndex = safeIndex;
    updateChapterUI();
  }

  function openModal() {
    modal.dataset.visible = 'true';
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    if (!videoEl.dataset.currentSrc) {
      selectChapter(currentChapterIndex);
    } else {
      updateChapterUI();
    }
  }

  function closeModal() {
    modal.dataset.visible = 'false';
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    if (videoEl) videoEl.pause();
    if (youtubeFrame && youtubeFrame.getAttribute('src')) {
      youtubeFrame.setAttribute('src', '');
      youtubeFrame.dataset.currentSrc = '';
    }
  }

  function handleKeydown(event) {
    if (modal.dataset.visible !== 'true') return;
    const activeChapters = getActiveChapters();
    if (!activeChapters.length) return;

    if (event.key === 'Escape') {
      closeModal();
      return;
    }

    if (event.key === 'ArrowDown') {
      event.preventDefault();
      const next = (currentChapterIndex + 1) % activeChapters.length;
      selectChapter(next);
    }

    if (event.key === 'ArrowUp') {
      event.preventDefault();
      const prev = (currentChapterIndex - 1 + activeChapters.length) % activeChapters.length;
      selectChapter(prev);
    }
  }

  function updateTrackButtons() {
    if (!trackButtons || !trackButtons.length) return;
    trackButtons.forEach((btn) => {
      const isActive = btn.dataset.helpTrack === currentTrack;
      btn.classList.toggle('cdp-help-track-btn--active', isActive);
      btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });
  }

  function selectTrack(track) {
    if (!tutorialTracks[track] || track === currentTrack) {
      return;
    }
    currentTrack = track;
    currentChapterIndex = 0;
    updateTrackButtons();
    renderChapterButtons();
    selectChapter(0);
  }

  function openWithOptions(options = {}) {
    if (options.track && tutorialTracks[options.track]) {
      if (options.track !== currentTrack) {
        selectTrack(options.track);
      }
    }
    if (typeof options.chapter === 'number') {
      selectChapter(options.chapter);
    }
    openModal();
  }

  helpButton.addEventListener('click', (event) => {
    event.preventDefault();
    openWithOptions();
  });

  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  if (backdrop) backdrop.addEventListener('click', closeModal);
  document.addEventListener('keydown', handleKeydown);

  if (trackButtons.length) {
    trackButtons.forEach((btn) => {
      btn.addEventListener('click', () => {
        const track = btn.dataset.helpTrack;
        if (track) {
          selectTrack(track);
        }
      });
    });
  }

  document.addEventListener('cdp:help:open', (event) => {
    const detail = event.detail || {};
    openWithOptions(detail);
  });

  window.cdpHelp = {
    open: (options) => openWithOptions(options || {})
  };

  updateTrackButtons();
  renderChapterButtons();
  selectChapter(0);
});
