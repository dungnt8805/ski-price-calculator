/**
 * Facilities Tag Management for Hotel Admin Form
 */
(function ($) {
  'use strict';

  var facilitiesInput = document.getElementById('spcu_facilities_input');
  var facilitiesTags = document.getElementById('spcu-facilities-tags');
  var facilitiesAutocomplete = document.getElementById('spcu-facility-autocomplete');
  var facilitiesHidden = document.getElementById('spcu_facilities_hidden');
  var currentFacilities = [];
  var allFacilities = [];

  var hotelId = parseInt(document.querySelector('[name="hotel_id"]')?.value || 0);
  var restUrl = spcu_facilities_data?.rest_url || '';
  var nonce = spcu_facilities_data?.nonce || '';

  if (!facilitiesInput || !facilitiesTags || !facilitiesHidden || !restUrl) {
    console.error('Facilities form elements or REST URL not found');
    return;
  }

  async function loadAllFacilities() {
    try {
      var response = await fetch(restUrl + 'spcu_facility?per_page=100');
      if (!response.ok) throw new Error('Failed to fetch facilities');
      var data = await response.json();
      allFacilities = Array.isArray(data) ? data : [];
      console.log('Loaded ' + allFacilities.length + ' facilities');
    } catch (e) {
      console.error('Failed to load facilities:', e);
    }
  }

  async function loadHotelFacilities() {
    if (hotelId <= 0) return;
    try {
      var response = await fetch(restUrl + 'spcu_hotel/' + hotelId);
      if (!response.ok) throw new Error('Failed to fetch hotel');
      var post = await response.json();
      if (post.spcu_facility && Array.isArray(post.spcu_facility)) {
        currentFacilities = post.spcu_facility.map(function (termId) {
          var term = allFacilities.find(function (t) { return t.id === termId; });
          return { id: termId, name: term ? term.name : 'Facility #' + termId };
        });
        updateHiddenField();
        renderFacilityTags();
        console.log('Loaded hotel facilities:', currentFacilities);
      }
    } catch (e) {
      console.error('Failed to load hotel facilities:', e);
    }
  }

  function updateHiddenField() {
    if (facilitiesHidden) {
      facilitiesHidden.value = JSON.stringify(currentFacilities.map(function (f) { return f.id; }));
    }
  }

  function renderFacilityTags() {
    facilitiesTags.innerHTML = '';
    currentFacilities.forEach(function (facility, idx) {
      var chip = document.createElement('span');
      chip.style.cssText = 'display:inline-flex;align-items:center;gap:6px;background:#f0f6fc;border:1px solid #2271b1;border-radius:16px;padding:4px 12px;font-size:13px;color:#1d2327;';

      var label = document.createElement('span');
      label.textContent = facility.name || facility.id;
      chip.appendChild(label);

      var removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.style.cssText = 'background:none;border:none;color:#c00;cursor:pointer;font-weight:bold;padding:0;font-size:14px;line-height:1;margin-left:6px;';
      removeBtn.textContent = '×';
      removeBtn.onclick = function (e) {
        e.preventDefault();
        currentFacilities.splice(idx, 1);
        updateHiddenField();
        renderFacilityTags();
      };
      chip.appendChild(removeBtn);
      facilitiesTags.appendChild(chip);
    });
  }

  function escapeHtml(text) {
    var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, function (m) { return map[m]; });
  }

  facilitiesInput.addEventListener('input', function (e) {
    var val = this.value.trim().toLowerCase();
    facilitiesAutocomplete.innerHTML = '';

    if (val.length < 1) {
      facilitiesAutocomplete.style.display = 'none';
      return;
    }

    var matches = allFacilities.filter(function (f) {
      var isSelected = currentFacilities.find(function (c) { return c.id === f.id; });
      return f.name.toLowerCase().includes(val) && !isSelected;
    });

    if (matches.length === 0 && val) {
      var item = document.createElement('div');
      item.style.cssText = 'padding:8px 12px;color:#999;font-style:italic;cursor:pointer;';
      item.textContent = 'Press Enter to create "' + escapeHtml(val) + '"';
      item.onclick = function () { createAndSelectFacility(val); };
      facilitiesAutocomplete.appendChild(item);
    } else if (matches.length > 0) {
      matches.forEach(function (facility) {
        var item = document.createElement('div');
        item.style.cssText = 'padding:8px 12px;cursor:pointer;border-bottom:1px solid #eee;transition:background 0.2s;';
        item.textContent = facility.name;
        item.onmouseover = function () { this.style.background = '#f5f5f5'; };
        item.onmouseout = function () { this.style.background = ''; };
        item.onclick = function () { selectFacility(facility.id, facility.name); };
        facilitiesAutocomplete.appendChild(item);
      });
    }

    facilitiesAutocomplete.style.display = 'block';
  });

  function selectFacility(termId, termName) {
    if (!currentFacilities.find(function (f) { return f.id === termId; })) {
      currentFacilities.push({ id: termId, name: termName });
      updateHiddenField();
      renderFacilityTags();
    }
    facilitiesInput.value = '';
    facilitiesAutocomplete.style.display = 'none';
    facilitiesInput.focus();
  }

  facilitiesInput.addEventListener('keypress', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      var val = this.value.trim();
      if (val) {
        var existing = allFacilities.find(function (f) {
          return f.name.toLowerCase() === val.toLowerCase();
        });
        if (existing) {
          selectFacility(existing.id, existing.name);
        } else {
          createAndSelectFacility(val);
        }
      }
    }
  });

  async function createAndSelectFacility(name) {
    if (!restUrl || !nonce) {
      console.error('REST URL or nonce not available');
      return;
    }
    try {
      var response = await fetch(restUrl + 'spcu_facility', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': nonce,
        },
        body: JSON.stringify({ name: name })
      });

      if (response.ok) {
        var newTerm = await response.json();
        allFacilities.push(newTerm);
        selectFacility(newTerm.id, newTerm.name);
        console.log('Created new facility:', newTerm);
      } else {
        var errorText = await response.text();
        console.error('Failed to create facility. Status:', response.status, 'Response:', errorText);
        alert('Could not create facility. Check console for details.');
      }
    } catch (e) {
      console.error('Failed to create facility:', e);
      alert('Error creating facility: ' + e.message);
    }
  }

  // Hide autocomplete when clicking outside
  document.addEventListener('click', function (e) {
    if (e.target !== facilitiesInput && !facilitiesAutocomplete.contains(e.target)) {
      facilitiesAutocomplete.style.display = 'none';
    }
  });

  // Initialize on page load
  loadAllFacilities().then(function () {
    loadHotelFacilities();
  });

})(jQuery);
