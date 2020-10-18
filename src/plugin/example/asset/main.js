
window.addEventListener('load', function() {

  // Listen to all Deft form Select fields, for the JS Select drop-in
  let deftFieldSelects = document.querySelectorAll(".deft.field.select .control");
  let deftFieldSelectElement, container, i, j, el;
  for (i = 0; i < deftFieldSelects.length; i++)  {
    container = deftFieldSelects[i].querySelector('.js-container');
    deftFieldSelectElement = deftFieldSelects[i].querySelector('select');

    // Populate JS Select options from Select element
    if (deftFieldSelectElement.options.length) {

      // Maximum number of options to display
      let max = Math.max(
        1,
        deftFieldSelectElement.getAttribute('size')
      );

      let options = container.querySelector('.options');
      options.innerHTML = '';
      for (j = 0; j < deftFieldSelectElement.options.length; j++) {
        el = document.createElement('div');

        // Multiple options displayed
        if (max > 1 && j < max) {
          options.setAttribute('tabindex', -1);
          el.innerText = '&nbsp;';

          // Pad the JS container height, beyond one option
          if (j)
            container.insertBefore(el, container.firstChild);

          // Recreate element for the option
          el = document.createElement('div');
          el.classList.add('show');
        }

        // Size is one, show first item
        else if (j === 0 && max === 1)
          el.classList.add('show');

        el.classList.add('option');
        el.setAttribute('data-value', deftFieldSelectElement.options[j].value);
        if (deftFieldSelectElement.options[j].selected === true) {
          el.setAttribute('selected', true);
        }

        el.innerText = deftFieldSelectElement.options[j].innerText;
        options.appendChild(el);
      }
    }

    // Clicking the JS Select container
    container.addEventListener('click', function(event){

      // Only on a JS Select option
      if (event.target.classList.contains('option')) {

        // Find index of the selected item
        let i = 0;
        let child = event.target;
        while ((child = child.previousSibling) !== null)
          i++;

        // Mark Select element option as selected
        // this.parentNode.querySelector('select').selectedIndex = i;
        let selectElement = this.parentNode.querySelector('select');
        selectElement.options[i].selected = !selectElement.options[i].selected;

        // Maximum number of options to display
        let max = Math.max(
          1,
          selectElement.getAttribute('size')
        );

        // Hold engagement, for possible multiple selections
        if (max > 1)
          selectElement.focus();

        // Update JS Select options with new index
        let options = this.querySelectorAll('.options .option');
        for (let j = 0; j < options.length; j++) {
          if (selectElement.options[j].selected === true) {
            options[j].setAttribute('selected', true);
            if (max === 1)
              options[j].classList.add('show');
          } else {
            options[j].removeAttribute('selected');
            if (max === 1)
              options[j].classList.remove('show');
          }
        }
      }
    });

    // Select element changed, update JS Select options
    deftFieldSelectElement.addEventListener('change', function(event) {console.log(event);
      let i, j;
      let options = this.parentNode.querySelectorAll('.options .option');
      for (i = 0; i < options.length; i++) {
        options[i].removeAttribute('selected');
        for (j = 0; j < this.selectedOptions.length; j++) {
          if (i === this.selectedOptions[j].index && this.selectedOptions[j].selected === true)
            options[i].setAttribute('selected', true);
        }
      }
    })
  }
});