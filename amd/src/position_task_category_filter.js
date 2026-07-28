define([], function() {
    var selectors = {
        chipsContainer: '[data-region="position-task-category-chips"]',
        taskSelect: 'id_taskids',
        departmentSelect: 'id_departmentid',
        allChip: '[data-taskcat-chip="all"]',
        categoryChip: '[data-taskcat-chip]:not([data-taskcat-chip="all"])',
        chip: '[data-taskcat-chip]',
    };

    var triggerSelectChange = function(select) {
        if (window.jQuery && window.jQuery(select).data('select2')) {
            window.jQuery(select).trigger('change.select2');
            return;
        }

        var event = new Event('change', {bubbles: true});
        select.dispatchEvent(event);
    };

    var init = function(categoryByTask, departmentByCategory) {
        var chipsContainer = document.querySelector(selectors.chipsContainer);
        var select = document.getElementById(selectors.taskSelect);
        var departmentSelect = document.getElementById(selectors.departmentSelect);
        if (!chipsContainer || !select || chipsContainer.dataset.initialized === '1') {
            return;
        }
        chipsContainer.dataset.initialized = '1';

        var activeCategories = [];
        var allOptions = Array.prototype.map.call(select.options, function(option) {
            return {
                value: String(option.value || ''),
                text: option.text,
                selected: option.selected === true,
            };
        });

        var getSelectedDepartment = function() {
            return departmentSelect ? String(departmentSelect.value || '') : '';
        };

        var setChipActive = function(chip, active) {
            chip.classList.toggle('local-dutydesk-filter-chip--active', active);
            chip.setAttribute('aria-pressed', active ? 'true' : 'false');
        };

        var updateCategoryVisibility = function() {
            var selectedDepartment = getSelectedDepartment();
            var allChip = chipsContainer.querySelector(selectors.allChip);
            var categoryChips = chipsContainer.querySelectorAll(selectors.categoryChip);
            var visibleActiveCategories = [];

            categoryChips.forEach(function(chip) {
                var category = chip.getAttribute('data-taskcat-chip');
                var categoryDepartment = String(chip.getAttribute('data-taskcat-department') || '');
                var visible = !selectedDepartment || categoryDepartment === selectedDepartment;
                chip.hidden = !visible;

                if (!visible && chip.classList.contains('local-dutydesk-filter-chip--active')) {
                    setChipActive(chip, false);
                }
                if (visible && chip.classList.contains('local-dutydesk-filter-chip--active')) {
                    visibleActiveCategories.push(category);
                }
            });

            activeCategories = visibleActiveCategories;
            if (allChip) {
                setChipActive(allChip, activeCategories.length === 0);
            }
        };

        var applyFilter = function() {
            var hasFilter = activeCategories.length > 0;
            var selectedDepartment = getSelectedDepartment();
            var selectedValues = {};
            Array.prototype.forEach.call(select.options, function(option) {
                if (option.selected && option.value) {
                    selectedValues[String(option.value)] = true;
                }
            });

            while (select.firstChild) {
                select.removeChild(select.firstChild);
            }

            allOptions.forEach(function(optiondata) {
                if (!optiondata.value) {
                    select.add(new Option(optiondata.text, optiondata.value, false, false));
                    return;
                }

                var category = String(categoryByTask[optiondata.value] || 0);
                var categoryDepartment = String(departmentByCategory[category] || '');
                var keepSelected = !!selectedValues[optiondata.value];
                var departmentAllowed = !selectedDepartment || categoryDepartment === selectedDepartment;
                var allowed = departmentAllowed && (!hasFilter || activeCategories.indexOf(category) !== -1);
                if (!allowed && !keepSelected) {
                    return;
                }

                select.add(new Option(optiondata.text, optiondata.value, false, keepSelected));
            });

            triggerSelectChange(select);
        };

        chipsContainer.addEventListener('click', function(event) {
            var chip = event.target.closest(selectors.chip);
            if (!chip) {
                return;
            }
            event.preventDefault();

            var value = chip.getAttribute('data-taskcat-chip');
            var allChip = chipsContainer.querySelector(selectors.allChip);
            var categoryChips = chipsContainer.querySelectorAll(selectors.categoryChip);

            if (value === 'all') {
                activeCategories = [];
                setChipActive(allChip, true);
                categoryChips.forEach(function(categoryChip) {
                    setChipActive(categoryChip, false);
                });
                applyFilter();
                chip.blur();
                return;
            }

            if (!allChip) {
                return;
            }

            setChipActive(allChip, false);
            var isActive = chip.classList.contains('local-dutydesk-filter-chip--active');
            setChipActive(chip, !isActive);

            activeCategories = [];
            categoryChips.forEach(function(categoryChip) {
                if (categoryChip.classList.contains('local-dutydesk-filter-chip--active')) {
                    activeCategories.push(categoryChip.getAttribute('data-taskcat-chip'));
                }
            });

            if (activeCategories.length === 0) {
                setChipActive(allChip, true);
            }
            applyFilter();
            chip.blur();
        });

        if (departmentSelect) {
            departmentSelect.addEventListener('change', function() {
                updateCategoryVisibility();
                applyFilter();
            });
        }

        updateCategoryVisibility();
        applyFilter();
    };

    return {
        init: init,
    };
});
