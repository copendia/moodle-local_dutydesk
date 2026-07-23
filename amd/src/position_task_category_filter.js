define([], function() {
    const selectors = {
        chipsContainer: '[data-region="position-task-category-chips"]',
        taskSelect: 'id_taskids',
        departmentSelect: 'id_departmentid',
        allChip: '[data-taskcat-chip="all"]',
        categoryChip: '[data-taskcat-chip]:not([data-taskcat-chip="all"])',
        chip: '[data-taskcat-chip]',
    };

    const triggerSelectChange = function(select) {
        if (window.jQuery && window.jQuery(select).data('select2')) {
            window.jQuery(select).trigger('change.select2');
            return;
        }

        const event = new Event('change', {bubbles: true});
        select.dispatchEvent(event);
    };

    const init = function(categoryByTask, departmentByCategory) {
        const chipsContainer = document.querySelector(selectors.chipsContainer);
        const select = document.getElementById(selectors.taskSelect);
        const departmentSelect = document.getElementById(selectors.departmentSelect);
        if (!chipsContainer || !select || chipsContainer.dataset.initialized === '1') {
            return;
        }
        chipsContainer.dataset.initialized = '1';

        let activeCategories = [];
        const allOptions = Array.prototype.map.call(select.options, function(option) {
            return {
                value: String(option.value || ''),
                text: option.text,
                selected: option.selected === true,
            };
        });

        const getSelectedDepartment = function() {
            return departmentSelect ? String(departmentSelect.value || '') : '';
        };

        const setChipActive = function(chip, active) {
            chip.classList.toggle('local-dutydesk-filter-chip--active', active);
            chip.setAttribute('aria-pressed', active ? 'true' : 'false');
        };

        const updateCategoryVisibility = function() {
            const selectedDepartment = getSelectedDepartment();
            const allChip = chipsContainer.querySelector(selectors.allChip);
            const categoryChips = chipsContainer.querySelectorAll(selectors.categoryChip);
            const visibleActiveCategories = [];

            categoryChips.forEach(function(chip) {
                const category = chip.getAttribute('data-taskcat-chip');
                const categoryDepartment = String(chip.getAttribute('data-taskcat-department') || '');
                const visible = !selectedDepartment || categoryDepartment === selectedDepartment;
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

        const applyFilter = function() {
            const hasFilter = activeCategories.length > 0;
            const selectedDepartment = getSelectedDepartment();
            const selectedValues = {};
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

                const category = String(categoryByTask[optiondata.value] || 0);
                const categoryDepartment = String(departmentByCategory[category] || '');
                const keepSelected = !!selectedValues[optiondata.value];
                const departmentAllowed = !selectedDepartment || categoryDepartment === selectedDepartment;
                const allowed = departmentAllowed && (!hasFilter || activeCategories.indexOf(category) !== -1);
                if (!allowed && !keepSelected) {
                    return;
                }

                select.add(new Option(optiondata.text, optiondata.value, false, keepSelected));
            });

            triggerSelectChange(select);
        };

        chipsContainer.addEventListener('click', function(event) {
            const chip = event.target.closest(selectors.chip);
            if (!chip) {
                return;
            }
            event.preventDefault();

            const value = chip.getAttribute('data-taskcat-chip');
            const allChip = chipsContainer.querySelector(selectors.allChip);
            const categoryChips = chipsContainer.querySelectorAll(selectors.categoryChip);

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
            const isActive = chip.classList.contains('local-dutydesk-filter-chip--active');
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
