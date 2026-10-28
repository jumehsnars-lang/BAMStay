(() => {
  'use strict';

  const imagePath = (filename) => `assets/images/${encodeURIComponent(filename)}`;
  const roomDataElement = document.getElementById('room-data');
  const rooms = roomDataElement ? JSON.parse(roomDataElement.textContent) : [];
  const roomById = new Map(rooms.map((room) => [room.id, room]));

  const menuToggle = document.getElementById('mobile-menu-toggle');
  const mobileMenu = document.getElementById('mobile-menu');
  if (menuToggle && mobileMenu) {
    menuToggle.addEventListener('click', () => {
      const isOpening = mobileMenu.classList.contains('hidden');
      mobileMenu.classList.toggle('hidden', !isOpening);
      menuToggle.setAttribute('aria-expanded', String(isOpening));
      menuToggle.setAttribute('aria-label', isOpening ? 'Close menu' : 'Open menu');
      menuToggle.innerHTML = isOpening
        ? '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m18 6-12 12"/><path d="m6 6 12 12"/></svg>'
        : '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/></svg>';
    });
    mobileMenu.querySelectorAll('a[href^="#"]').forEach((link) => {
      link.addEventListener('click', () => {
        mobileMenu.classList.add('hidden');
        menuToggle.setAttribute('aria-expanded', 'false');
        menuToggle.setAttribute('aria-label', 'Open menu');
        menuToggle.innerHTML = '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/></svg>';
      });
    });
  }

  const carousel = document.getElementById('room-carousel');
  if (carousel) {
    document.querySelectorAll('[data-room-move]').forEach((button) => {
      button.addEventListener('click', () => {
        const firstCard = carousel.firstElementChild;
        if (!firstCard) return;
        const gap = Number.parseFloat(getComputedStyle(carousel).columnGap) || 0;
        const direction = Number(button.dataset.roomMove);
        carousel.scrollBy({
          left: direction * (firstCard.getBoundingClientRect().width + gap),
          behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth',
        });
      });
    });
  }

  const roomBackdrop = document.getElementById('room-modal-backdrop');
  const roomModal = document.getElementById('room-modal');
  const roomModalImage = document.getElementById('room-modal-image');
  const roomModalBadge = document.getElementById('room-modal-badge');
  const roomModalTitle = document.getElementById('room-modal-title');
  const roomModalDescription = document.getElementById('room-modal-description');
  const roomModalRate = document.getElementById('room-modal-rate');
  const roomModalCounts = document.getElementById('room-modal-counts');
  const roomModalBeds = document.getElementById('room-modal-beds');
  const roomModalBedImage = document.getElementById('room-modal-bed-image');
  const roomModalBedStatus = document.getElementById('room-modal-bed-status');
  const roomModalBedTitle = document.getElementById('room-modal-bed-title');
  const roomModalBedRate = document.getElementById('room-modal-bed-rate');
  const roomModalInquire = document.getElementById('room-modal-inquire');
  const inquiryRoom = document.getElementById('room-select');
  const inquiryBed = document.getElementById('bed-select');
  const inquiryPreviewImage = document.getElementById('inquiry-preview-image');
  const inquiryPreviewBed = document.getElementById('inquiry-preview-bed');
  const inquiryPreviewTitle = document.getElementById('inquiry-preview-title');
  const inquiryPreviewRate = document.getElementById('inquiry-preview-rate');
  const inquiryPreviewDescription = document.getElementById('inquiry-preview-description');
  const inquiryPreviewCounts = document.getElementById('inquiry-preview-counts');
  let activeRoom = null;
  let activeModalBed = null;
  let roomTrigger = null;
  let previousBodyOverflow = '';
  let roomModalCloseTimer = 0;

  const availableBeds = (room) => room.beds.filter((bed) => bed.status === 'available');
  const modalFocusable = () => roomModal.querySelectorAll('button:not([disabled]), [href], select, input, textarea, [tabindex]:not([tabindex="-1"])');

  function renderModalCounts(room) {
    roomModalCounts.innerHTML = '';
    [['Total', room.counts.total], ['Occupied', room.counts.occupied], ['Available', room.counts.available]].forEach(([label, count]) => {
      const item = document.createElement('div');
      item.className = 'rounded-xl bg-muted py-2';
      const term = document.createElement('dt');
      term.className = 'text-[0.6rem] uppercase tracking-widest text-muted-foreground';
      term.textContent = label;
      const definition = document.createElement('dd');
      definition.className = 'font-serif text-2xl font-semibold';
      definition.textContent = String(count);
      item.append(term, definition);
      roomModalCounts.append(item);
    });
  }

  function updateModalBed(bed) {
    activeModalBed = bed;
    roomModalBedImage.src = imagePath(bed.image || activeRoom.image);
    roomModalBedImage.alt = `${activeRoom.name} photo for ${bed.label}`;
    roomModalBedTitle.textContent = bed.label;
    roomModalBedRate.textContent = `${activeRoom.name} · ${activeRoom.rate}${activeRoom.monthlyRate === null ? '' : ' / month'}`;
    roomModalBedStatus.textContent = bed.status === 'available'
      ? 'Available bedspace'
      : bed.status === 'occupied' ? 'Currently occupied' : 'Under maintenance';
    roomModalBedStatus.className = `text-xs uppercase tracking-widest ${bed.status === 'available' ? 'text-success' : 'text-muted-foreground'}`;
    roomModalBeds.querySelectorAll('[data-bed-id]').forEach((button) => {
      const buttonBed = activeRoom.beds.find((item) => item.id === button.dataset.bedId);
      if (!buttonBed) return;
      const selected = button.dataset.bedId === bed.id;
      button.setAttribute('aria-pressed', String(selected));
      button.className = `rounded-xl border p-3 text-left text-sm transition-colors ${
        buttonBed.status === 'available' && selected
          ? 'border-primary bg-primary/10 text-primary'
          : buttonBed.status === 'available'
            ? 'border-border hover:border-primary'
            : buttonBed.status === 'occupied'
              ? 'border-border bg-muted text-muted-foreground hover:border-primary/50'
              : 'border-border bg-muted text-muted-foreground'
      }`;
    });
    roomModalInquire.textContent = bed.status === 'available'
      ? `Inquire About ${bed.label}`
      : activeRoom.counts.available === 0 ? 'Ask About Waitlist' : 'Choose an Available Bedspace';
  }

  function openRoomModal(room, trigger) {
    if (!room || !roomBackdrop) return;
    activeRoom = room;
    roomTrigger = trigger;
    previousBodyOverflow = document.body.style.overflow;
    const freeBeds = availableBeds(room);
    roomModalBadge.textContent = room.counts.available > 0 ? `${room.counts.available} available` : 'Fully occupied';
    roomModalBadge.className = `rounded-full px-3 py-1 text-[0.65rem] font-medium uppercase tracking-widest text-primary-foreground ${room.counts.available > 0 ? 'bg-success' : 'bg-primary-deep'}`;
    roomModalTitle.textContent = room.name;
    roomModalDescription.textContent = room.description;
    roomModalRate.textContent = `${room.rate}${room.monthlyRate === null ? '' : ' / bedspace / month'}`;
    renderModalCounts(room);
    roomModalBeds.innerHTML = '';
    room.beds.forEach((bed) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.dataset.bedId = bed.id;
      button.setAttribute('aria-pressed', 'false');
      button.className = 'rounded-xl border border-border p-3 text-left text-sm transition-colors';
      const label = document.createElement('span');
      label.className = 'block font-medium';
      label.textContent = bed.label;
      const status = document.createElement('span');
      status.className = 'text-xs capitalize';
      status.textContent = bed.status;
      button.append(label, status);
      button.addEventListener('click', () => updateModalBed(bed));
      roomModalBeds.append(button);
    });
    const initialBed = freeBeds[0] || room.beds[0];
    roomModalImage.src = imagePath(room.image);
    roomModalImage.alt = `${room.name} room photo`;
    if (initialBed) updateModalBed(initialBed);

    window.clearTimeout(roomModalCloseTimer);
    roomBackdrop.classList.remove('hidden', 'is-closing', 'is-visible');
    roomBackdrop.classList.add('grid');
    roomBackdrop.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    window.requestAnimationFrame(() => {
      if (!roomBackdrop.classList.contains('hidden')) {
        roomBackdrop.classList.add('is-visible');
      }
    });
    document.getElementById('room-modal-close').focus();
  }

  function closeRoomModal() {
    if (!roomBackdrop || roomBackdrop.classList.contains('hidden') || roomBackdrop.classList.contains('is-closing')) return;
    roomBackdrop.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = previousBodyOverflow;
    activeRoom = null;
    if (roomTrigger) roomTrigger.focus();
    roomBackdrop.classList.remove('is-visible');
    roomBackdrop.classList.add('is-closing');
    roomModalCloseTimer = window.setTimeout(() => {
      roomBackdrop.classList.add('hidden');
      roomBackdrop.classList.remove('grid', 'is-closing');
    }, window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 190);
  }

  document.querySelectorAll('[data-open-room]').forEach((button) => {
    button.addEventListener('click', () => openRoomModal(roomById.get(button.dataset.openRoom), button));
  });
  document.getElementById('room-modal-close')?.addEventListener('click', closeRoomModal);
  roomBackdrop?.addEventListener('click', (event) => {
    if (event.target === roomBackdrop) closeRoomModal();
  });
  roomModal?.addEventListener('keydown', (event) => {
    if (event.key !== 'Tab') return;
    const focusable = [...modalFocusable()];
    if (focusable.length === 0) return;
    if (event.shiftKey && document.activeElement === focusable[0]) {
      event.preventDefault();
      focusable[focusable.length - 1].focus();
    } else if (!event.shiftKey && document.activeElement === focusable[focusable.length - 1]) {
      event.preventDefault();
      focusable[0].focus();
    }
  });
  roomModalInquire?.addEventListener('click', () => {
    if (!activeRoom) return;
    const bed = activeModalBed?.status === 'available'
      ? activeModalBed
      : availableBeds(activeRoom)[0];
    inquiryRoom.value = activeRoom.id;
    inquiryRoom.dispatchEvent(new Event('change', { bubbles: true }));
    if (bed) inquiryBed.value = bed.id;
    inquiryBed.dispatchEvent(new Event('change', { bubbles: true }));
    closeRoomModal();
    document.getElementById('inquire').scrollIntoView({
      behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth',
    });
  });

  function updateInquiryPreview() {
    const room = roomById.get(inquiryRoom?.value);
    if (!room) return;
    const freeBeds = availableBeds(room);
    inquiryBed.innerHTML = '';
    const anyOption = document.createElement('option');
    anyOption.value = '';
    anyOption.textContent = 'Any available';
    inquiryBed.append(anyOption);
    freeBeds.forEach((bed) => {
      const option = document.createElement('option');
      option.value = bed.id;
      option.textContent = bed.label;
      inquiryBed.append(option);
    });
    inquiryBed.disabled = freeBeds.length === 0;
    const requestedBedId = inquiryBed.dataset.current;
    let selected = requestedBedId ? freeBeds.find((bed) => bed.id === requestedBedId) : null;
    if (requestedBedId && !selected) selected = freeBeds[0] || null;
    inquiryBed.value = selected?.id || '';
    if (requestedBedId) inquiryBed.dataset.current = inquiryBed.value;
    const previewBed = selected || freeBeds[0] || null;

    inquiryPreviewImage.src = imagePath(previewBed?.image || room.image);
    inquiryPreviewImage.alt = `${room.name}${previewBed ? ` ${previewBed.label}` : ''} photo`;
    inquiryPreviewBed.textContent = previewBed ? `${previewBed.label} · Available` : '';
    inquiryPreviewTitle.textContent = `${room.name}${previewBed ? ` · ${previewBed.label}` : ''}`;
    inquiryPreviewRate.innerHTML = '';
    inquiryPreviewRate.append(document.createTextNode(room.rate));
    if (room.monthlyRate !== null) {
      const monthly = document.createElement('span');
      monthly.className = 'font-sans text-xs text-muted-foreground';
      monthly.textContent = ' /mo';
      inquiryPreviewRate.append(monthly);
    }
    inquiryPreviewDescription.textContent = room.description;
    inquiryPreviewCounts.textContent = `${room.counts.available} available · ${room.counts.occupied} occupied`;
  }

  if (inquiryRoom && inquiryBed) {
    inquiryBed.dataset.current = inquiryBed.value;
    inquiryRoom.addEventListener('change', () => {
      const nextRoom = roomById.get(inquiryRoom.value);
      inquiryBed.dataset.current = availableBeds(nextRoom || { beds: [] })[0]?.id || '';
      updateInquiryPreview();
    });
    inquiryBed.addEventListener('change', () => {
      inquiryBed.dataset.current = inquiryBed.value;
      updateInquiryPreview();
    });
    updateInquiryPreview();
  }

  const galleryImages = [...document.querySelectorAll('[data-gallery-photo]')].map((button) => ({
    button,
    image: button.querySelector('img'),
  }));
  const galleryTrack = document.getElementById('gallery-track');
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  let galleryOffset = 0;
  let previousFrameTime = 0;
  let frameId = 0;
  let manualTransitionTimer = 0;
  let manualMoveUntil = 0;

  function galleryCycleWidth() {
    return galleryTrack?.firstElementChild?.getBoundingClientRect().width || 0;
  }

  function moveGallery(direction) {
    if (!galleryTrack || galleryImages.length === 0) return;
    const cycleWidth = galleryCycleWidth();
    const firstImage = galleryImages[0].button;
    const gap = Number.parseFloat(getComputedStyle(firstImage.parentElement).columnGap) || 0;
    const step = firstImage.getBoundingClientRect().width + gap;
    if (cycleWidth <= 0) return;
    galleryOffset = (galleryOffset + direction * step + cycleWidth) % cycleWidth;
    galleryTrack.style.transition = reducedMotion.matches
      ? 'none'
      : 'transform 450ms cubic-bezier(0.22, 1, 0.36, 1)';
    galleryTrack.style.transform = `translate3d(${-galleryOffset}px, 0, 0)`;
    manualMoveUntil = performance.now() + (reducedMotion.matches ? 0 : 470);
    window.clearTimeout(manualTransitionTimer);
    manualTransitionTimer = window.setTimeout(() => {
      if (galleryTrack) galleryTrack.style.transition = '';
    }, reducedMotion.matches ? 0 : 470);
  }

  if (galleryTrack) {
    const pauseGallery = () => {
      manualMoveUntil = Number.POSITIVE_INFINITY;
      previousFrameTime = 0;
    };
    const resumeGallery = () => {
      manualMoveUntil = 0;
      previousFrameTime = 0;
    };
    galleryTrack.addEventListener('pointerenter', pauseGallery);
    galleryTrack.addEventListener('pointerleave', resumeGallery);
    galleryTrack.addEventListener('focusin', pauseGallery);
    galleryTrack.addEventListener('focusout', (event) => {
      if (!galleryTrack.contains(event.relatedTarget)) resumeGallery();
    });
  }

  document.querySelectorAll('[data-gallery-move]').forEach((button) => {
    button.addEventListener('click', () => moveGallery(Number(button.dataset.galleryMove)));
  });

  if (galleryTrack && galleryImages.length > 1 && !reducedMotion.matches) {
    const animateGallery = (time) => {
      if (previousFrameTime && time >= manualMoveUntil) {
        const cycleWidth = galleryCycleWidth();
        if (cycleWidth > 0) {
          galleryOffset = (galleryOffset + ((time - previousFrameTime) / 1000) * 32) % cycleWidth;
          galleryTrack.style.transform = `translate3d(${-galleryOffset}px, 0, 0)`;
        }
      }
      previousFrameTime = time;
      frameId = window.requestAnimationFrame(animateGallery);
    };
    frameId = window.requestAnimationFrame(animateGallery);
  }
  reducedMotion.addEventListener?.('change', () => {
    if (reducedMotion.matches) {
      window.cancelAnimationFrame(frameId);
      galleryTrack && (galleryTrack.style.transition = 'none');
    } else if (galleryTrack && galleryImages.length > 1) {
      previousFrameTime = 0;
      frameId = window.requestAnimationFrame(function animate(time) {
        if (previousFrameTime) {
          const cycleWidth = galleryCycleWidth();
          if (cycleWidth > 0) {
            galleryOffset = (galleryOffset + ((time - previousFrameTime) / 1000) * 32) % cycleWidth;
            galleryTrack.style.transform = `translate3d(${-galleryOffset}px, 0, 0)`;
          }
        }
        previousFrameTime = time;
        frameId = window.requestAnimationFrame(animate);
      });
    }
  });

  const galleryModal = document.getElementById('gallery-modal');
  const galleryModalImage = document.getElementById('gallery-modal-image');
  const galleryModalCaption = document.getElementById('gallery-modal-caption');
  let galleryModalIndex = 0;
  let galleryTrigger = null;

  function setGalleryModal(index) {
    if (galleryImages.length === 0) return;
    galleryModalIndex = (index + galleryImages.length) % galleryImages.length;
    const item = galleryImages[galleryModalIndex];
    galleryModalImage.src = item.image.currentSrc || item.image.src;
    galleryModalImage.alt = item.image.alt;
    galleryModalCaption.textContent = item.button.textContent.trim();
  }

  function openGalleryModal(index, trigger) {
    galleryTrigger = trigger;
    setGalleryModal(index);
    galleryModal.classList.remove('hidden');
    galleryModal.classList.add('grid');
    galleryModal.setAttribute('aria-hidden', 'false');
    previousBodyOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    galleryModal.querySelector('[data-gallery-close]').focus();
  }

  function closeGalleryModal() {
    galleryModal.classList.add('hidden');
    galleryModal.classList.remove('grid');
    galleryModal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = previousBodyOverflow;
    galleryTrigger?.focus();
  }

  galleryImages.forEach(({ button }) => {
    button.addEventListener('click', () => openGalleryModal(Number(button.dataset.galleryPhoto), button));
  });
  galleryModal?.querySelector('[data-gallery-close]')?.addEventListener('click', closeGalleryModal);
  galleryModal?.addEventListener('click', (event) => {
    if (event.target === galleryModal) closeGalleryModal();
  });
  galleryModal?.addEventListener('keydown', (event) => {
    if (event.key !== 'Tab') return;
    const focusable = [...galleryModal.querySelectorAll('button:not([disabled]), [tabindex]:not([tabindex="-1"])')];
    if (focusable.length === 0) return;
    if (event.shiftKey && document.activeElement === focusable[0]) {
      event.preventDefault();
      focusable[focusable.length - 1].focus();
    } else if (!event.shiftKey && document.activeElement === focusable[focusable.length - 1]) {
      event.preventDefault();
      focusable[0].focus();
    }
  });
  galleryModal?.querySelectorAll('[data-gallery-lightbox-move]').forEach((button) => {
    button.addEventListener('click', () => setGalleryModal(galleryModalIndex + Number(button.dataset.galleryLightboxMove)));
  });

  document.addEventListener('keydown', (event) => {
    if (!roomBackdrop?.classList.contains('hidden') && event.key === 'Escape') {
      closeRoomModal();
      return;
    }
    if (!galleryModal?.classList.contains('hidden')) {
      if (event.key === 'Escape') closeGalleryModal();
      if (event.key === 'ArrowLeft') setGalleryModal(galleryModalIndex - 1);
      if (event.key === 'ArrowRight') setGalleryModal(galleryModalIndex + 1);
    }
  });
})();
