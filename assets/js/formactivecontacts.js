const selectAllCheckbox = document.getElementById('select-all');
const singleCheckboxes = document.querySelectorAll('.single-check');
const selectedActionsDiv = document.getElementById('selected-actions');
let base = document.getElementById('base');
function createLinks() {
  // Remove existing links if any
  while (selectedActionsDiv.firstChild) {
    selectedActionsDiv.removeChild(selectedActionsDiv.firstChild);
  }

  const checks = document.querySelectorAll('.single-check:checked');
  const checkedCheckboxes = Array.from(checks);
  if (checkedCheckboxes.length === 0) {
    return; // No checkboxes are checked, exit function
  }

  const checkedValues = checkedCheckboxes
    .map((checkbox) => checkbox.value)
    .join(',');

  const unsubscribe = document.getElementById('base');
  const listtr = document.getElementsByClassName('list-unchanged');
  const link1 = document.createElement('a');
  const unsubscribeData = unsubscribe.getAttribute('data-un');

  if (unsubscribeData !== null && listtr.length > 0) {
    const listid = listtr[0].getAttribute('data');
    link1.href = `${base}/admin/config/active-campaign/lists/unsubscribe/${checkedValues}/${listid}`;
    link1.textContent = 'Unsubscribe All';
  } else {
    link1.href = `${base}/admin/config/active-campaign/contact/list/${checkedValues}`;
    link1.textContent = 'Assign List to Contacts';
  }
  selectedActionsDiv.appendChild(link1);

  const link2 = document.createElement('a');
  link2.href = `${base}/admin/config/active-campaign/contact/delete/${checkedValues}`;
  link2.textContent = 'Delete Contacts';
  selectedActionsDiv.appendChild(link2);

  const link3 = document.createElement('a');
  link3.href = `${base}/admin/config/active-campaign/contact/deal/${checkedValues}`;
  link3.textContent = 'Deal On Contacts';
  selectedActionsDiv.appendChild(link3);
}
function createLinksList() {
  // Remove existing links if any
  while (selectedActionsDiv.firstChild) {
    selectedActionsDiv.removeChild(selectedActionsDiv.firstChild);
  }

  const checkBoxes = document.querySelectorAll('.single-check:checked');
  const checkedCheckboxes = Array.from(checkBoxes);
  if (checkedCheckboxes.length === 0) {
    return; // No checkboxes are checked, exit function
  }

  const checkedValues = checkedCheckboxes
    .map((checkbox) => checkbox.value)
    .join(',');
  const link1 = document.createElement('a');
  link1.href = `${base}/admin/config/active-campaign/lists/delete/${checkedValues}`;
  link1.textContent = 'Delete All Lists';
  selectedActionsDiv.appendChild(link1);
}

document.addEventListener('DOMContentLoaded', () => {
  // Get all the TD elements with class 'selections'
  const tdElements = document.querySelectorAll('td.selections');

  if (tdElements.length > 0) {
    // Loop through each TD element
    for (let i = 0; i < tdElements.length; i++) {
      // Get the data attribute value from the table tag
      let dataValue = document.getElementById('list-table');
      let values = [];
      if (dataValue !== null) {
        dataValue = dataValue.getAttribute('ids');
        values = dataValue.split(',');
      }

      // Create a checkbox for each value and append it to the TD
      const checkbox = document.createElement('input');
      checkbox.type = 'checkbox';
      checkbox.value = values[i].trim(); // Trim to remove any whitespace
      checkbox.classList.add('single-check');

      // Append the checkbox to the TD element
      tdElements[i].appendChild(checkbox);
    }

    const all = document.getElementsByClassName('selections-all');
    const checkbox = document.createElement('input');
    checkbox.id = 'select-all';
    checkbox.type = 'checkbox';
    if (all.length > 0) {
      all[0].appendChild(checkbox);
    }
  }

  if (
    base !== null &&
    selectAllCheckbox !== null &&
    singleCheckboxes.length > 0 &&
    selectedActionsDiv !== null
  ) {
    base = base.getAttribute('data');
    selectAllCheckbox.addEventListener('change', () => {
      singleCheckboxes.forEach((checkbox) => {
        checkbox.checked = selectAllCheckbox.checked;
      });

      if (tdElements.length <= 0) {
        createLinks();
      } else {
        createLinksList();
      }
    });

    singleCheckboxes.forEach((checkbox) => {
      checkbox.addEventListener('change', () => {
        if (!this.checked) {
          selectAllCheckbox.checked = false;
        } else {
          selectAllCheckbox.checked = Array.from(singleCheckboxes).every(
            (singleCheckbox) => {
              return singleCheckbox.checked;
            },
          );
        }
        if (tdElements.length <= 0) {
          createLinks();
        } else {
          createLinksList();
        }
      });
    });
  }

  const acc = document.getElementsByClassName('accordion');
  if (acc.length > 0) {
    let i;
    for (i = 0; i < acc.length; i++) {
      acc[i].addEventListener('click', function () {
        this.classList.toggle('active');

        const panel = this.nextElementSibling;
        if (panel.style.display === 'block') {
          panel.style.display = 'none';
        } else {
          panel.style.display = 'block';
        }
      });
    }
  }
});
