/**
 * NSBM ArtCraft - Upload Artwork Controller
 * PNG validation, live preview, drag-and-drop, and submission pipeline
 */

const Uploader = (function() {
  let selectedFile = null;

  async function init() {
    // Check if user is logged in
    await App.checkAuth();
    const user = App.getUser();
    if (!user) {
      App.showToast('Please login to submit artwork.', 'warning');
      setTimeout(() => { window.location.href = 'login.html'; }, 800);
      return;
    }

    loadCategories();
    setupFileInput();
    setupForm();
  }

  async function loadCategories() {
    const select = document.getElementById('category-select');
    if (!select) return;

    try {
      const res = await fetch('api/categories.php?action=list');
      const data = await res.json();
      if (data.status === 'success') {
        const cats = data.data.categories || [];
        select.innerHTML = '<option value="">-- Select Art/Craft Category --</option>';
        cats.forEach(c => {
          select.innerHTML += `<option value="${c.id}">${c.name}</option>`;
        });
      }
    } catch (e) {
      console.error('Error loading categories for upload:', e);
    }
  }

  function setupFileInput() {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('artwork-file');
    const previewContainer = document.getElementById('preview-container');
    const previewImg = document.getElementById('preview-img');

    if (!dropZone || !fileInput) return;

    dropZone.addEventListener('click', () => fileInput.click());

    ['dragenter', 'dragover'].forEach(eventName => {
      dropZone.addEventListener(eventName, (e) => {
        e.preventDefault();
        dropZone.style.borderColor = 'var(--nsbm-green)';
        dropZone.style.background = 'var(--nsbm-green-subtle)';
      }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
      dropZone.addEventListener(eventName, (e) => {
        e.preventDefault();
        dropZone.style.borderColor = 'var(--border-color)';
        dropZone.style.background = '#FFFFFF';
      }, false);
    });

    dropZone.addEventListener('drop', (e) => {
      const files = e.dataTransfer.files;
      if (files.length > 0) {
        handleFileSelect(files[0]);
      }
    });

    fileInput.addEventListener('change', (e) => {
      if (e.target.files.length > 0) {
        handleFileSelect(e.target.files[0]);
      }
    });
  }

  function handleFileSelect(file) {
    if (!file) return;

    // Check PNG file format as requested
    const isPng = file.type === 'image/png' || file.name.toLowerCase().endsWith('.png');
    if (!isPng) {
      App.showToast('Please select a .png image file as required.', 'error');
      return;
    }

    if (file.size > 10 * 1024 * 1024) {
      App.showToast('Image size exceeds 10MB limit.', 'error');
      return;
    }

    selectedFile = file;

    const previewContainer = document.getElementById('preview-container');
    const previewImg = document.getElementById('preview-img');
    const fileNameSpan = document.getElementById('file-name-label');

    const reader = new FileReader();
    reader.onload = function(e) {
      previewImg.src = e.target.result;
      previewContainer.style.display = 'block';
      if (fileNameSpan) {
        fileNameSpan.textContent = `${file.name} (${(file.size / (1024*1024)).toFixed(2)} MB)`;
      }
    };
    reader.readAsDataURL(file);
  }

  function setupForm() {
    const form = document.getElementById('artwork-upload-form');
    if (!form) return;

    form.addEventListener('submit', async function(e) {
      e.preventDefault();

      if (!selectedFile) {
        App.showToast('Please select a PNG image for your artwork.', 'error');
        return;
      }

      const submitBtn = document.getElementById('submit-btn');
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Uploading image...';

      try {
        // Step 1: Upload PNG image file
        const formData = new FormData();
        formData.append('image', selectedFile);

        const uploadRes = await fetch('api/upload.php', {
          method: 'POST',
          body: formData
        });
        const uploadData = await uploadRes.json();

        if (uploadData.status !== 'success') {
          throw new Error(uploadData.message || 'Image upload failed.');
        }

        const uploadedImagePath = uploadData.data.image_path;

        // Step 2: Create Artwork Record
        submitBtn.innerHTML = 'Saving artwork details...';

        const payload = {
          title: document.getElementById('title-input').value.trim(),
          category_id: parseInt(document.getElementById('category-select').value),
          medium: document.getElementById('medium-input').value.trim(),
          dimensions: document.getElementById('dimensions-input').value.trim() || 'N/A',
          description: document.getElementById('description-input').value.trim(),
          image_path: uploadedImagePath
        };

        const createRes = await fetch('api/artworks.php?action=create', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const createData = await createRes.json();

        if (createData.status === 'success') {
          App.showToast('Artwork submitted successfully! It has been submitted for curator review.', 'success');
          setTimeout(() => {
            window.location.href = 'my-submissions.html';
          }, 1200);
        } else {
          throw new Error(createData.message || 'Could not save artwork.');
        }

      } catch (err) {
        App.showToast(err.message, 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Submit Artwork for Showcase';
      }
    });
  }

  return {
    init,
    handleFileSelect
  };
})();

document.addEventListener('DOMContentLoaded', Uploader.init);
