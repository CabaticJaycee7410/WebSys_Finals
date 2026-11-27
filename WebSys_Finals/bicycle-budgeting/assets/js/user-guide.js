
document.addEventListener('DOMContentLoaded', function () {
    var startButton = document.getElementById('startUserGuide');
    var panel = document.getElementById('userGuidePanel');
    var backdrop = document.getElementById('userGuideBackdrop');
    var title = document.getElementById('userGuideTitle');
    var description = document.getElementById('userGuideText');
    var count = document.getElementById('userGuideCount');
    var counter = document.getElementById('userGuideCounter');
    var progress = document.getElementById('userGuideProgress');
    var previousButton = document.getElementById('userGuidePrevious');
    var nextButton = document.getElementById('userGuideNext');
    var closeButton = document.getElementById('userGuideClose');

    if (!startButton || !panel || !backdrop || !title || !description || !count || !counter || !progress || !previousButton || !nextButton || !closeButton) {
        return;
    }

    var garageSteps = [
        { target: '[data-guide="garage-overview"]', title: 'Garage Overview', text: 'This section displays your total bicycles, saved build plans, combined build budget, and planned parts cost.' },
        { target: '[data-guide="build-bike"]', title: 'Build New Bike', text: 'Click this button to open the Bicycle Builder and create a new bicycle profile and build plan.' },
        { target: '[data-guide="bicycle-profiles"]', title: 'Bicycle Profiles', text: 'Your registered bicycles appear here. Each bicycle card contains its own saved build plans.' },
        { target: '[data-guide="edit-plan"]', title: 'Edit Plan', text: 'Click Edit Plan beside a saved build plan to open it in the Bicycle Builder and make changes.' },
        { target: '[data-guide="delete-plan"]', title: 'Delete Plan', text: 'Click Delete Plan to remove a saved build plan. A confirmation message will appear before the deletion is processed.' }
    ];

    var builderSteps = [
        { target: '[data-guide="builder-existing-bike"]', screen: 'type', title: 'Choose an Existing Bicycle', text: 'Select one of your saved bicycles here, or leave the default option to create a new bicycle build.' },
        { target: '[data-guide="builder-bike-type"]', screen: 'type', title: 'Choose a Bicycle Type', text: 'Choose the type of bicycle you want to build. The available components will depend on your selected bicycle type.' },
        { target: '[data-guide="builder-details"]', screen: 'type', title: 'Enter Bicycle Details', text: 'Enter your bicycle name, build plan name, optional brand and model, and target budget. Click Continue to Bicycle Builder when ready.' },
        { target: '[data-guide="builder-model"]', screen: 'model', title: 'Select Bicycle Components', text: 'Use the 2D bicycle model or the component list to choose the parts you want. Your selected parts and estimated cost appear in the current build panel.' },
        { target: '[data-guide="builder-search"]', screen: 'search', title: 'Search Marketplace Listings', text: 'Search for a part using a product keyword. You can filter listings by brand or budget, then sort the results.' },
        { target: '[data-guide="builder-confirm"]', screen: 'confirm', title: 'Review and Confirm a Part', text: 'Review the product listing and any compatibility warnings. Choose an available action before adding the part to your bicycle plan.' },
        { target: '[data-guide="builder-review"]', screen: 'review', title: 'Review and Save Your Build', text: 'Check your bicycle type, target budget, selected parts, estimated total, and remaining budget. Save your new plan or update your existing plan.' }
    ];

    var marketplaceSteps = [
        { target: '[data-guide="marketplace-keyword"]', title: 'Search for a Bicycle Part', text: 'Enter the name of the bicycle part you want to find. For example, you can search for brakes, pedals, wheels, or handlebars.' },
        { target: '[data-guide="marketplace-brand"]', title: 'Filter by Brand', text: 'Enter a brand name if you want to narrow your product search. This field is optional.' },
        { target: '[data-guide="marketplace-budget"]', title: 'Set a Maximum Price', text: 'Enter your maximum preferred price to filter products according to your budget. You can leave this blank if you do not need a price limit.' },
        { target: '[data-guide="marketplace-search"]', title: 'Search Live Products', text: 'Click this button to submit your search and look for matching live marketplace listings.' },
        { target: '[data-guide="marketplace-results"]', title: 'View Product Results', text: 'Matching product listings will appear here. Review their details and prices before deciding which products fit your bicycle plan.' }
    ];

    var expensesSteps = [
        { target: '[data-guide="expenses-overview"]', title: 'Expenses Overview', text: 'This section shows your recorded cycling expenses and gives you an overview of your spending.' },
        { target: '[data-guide="expenses-category"]', title: 'Choose an Expense Category', text: 'Select the category that best describes your expense so your spending can be organized.' },
        { target: '[data-guide="expenses-project"]', title: 'Select a Bicycle or Build Plan', text: 'Choose the related bicycle or build plan if the expense belongs to one. This helps organize your records.' },
        { target: '[data-guide="expenses-description"]', title: 'Enter Expense Details', text: 'Provide a short description of what you spent money on.' },
        { target: '[data-guide="expenses-amount"]', title: 'Enter the Amount', text: 'Enter the amount you spent. Make sure the value matches your actual expense.' },
        { target: '[data-guide="expenses-date"]', title: 'Set the Expense Date', text: 'Choose the date when the expense was made.' },
        { target: '[data-guide="expenses-save"]', title: 'Save Your Expense', text: 'Click this button to record your expense. Your information will be saved and added to your expense history.' },
        { target: '[data-guide="expenses-history"]', title: 'Expense History', text: 'View your previously recorded expenses here. Use the available actions to manage your records.' }
    ];

    var savingsSteps = [
        { target: '[data-guide="savings-overview"]', title: 'Savings Overview', text: 'This section displays your savings progress and helps you monitor the money you have set aside for your bicycle plans.' },
        { target: '[data-guide="savings-project"]', title: 'Select a Bicycle or Build Plan', text: 'Choose the bicycle or build plan connected to your savings.' },
        { target: '[data-guide="savings-amount"]', title: 'Enter Savings Amount', text: 'Enter the amount of money you want to record as savings.' },
        { target: '[data-guide="savings-date"]', title: 'Set the Savings Date', text: 'Choose the date when you added the money to your savings.' },
        { target: '[data-guide="savings-notes"]', title: 'Add Notes', text: 'You can add optional notes to describe your savings entry.' },
        { target: '[data-guide="savings-save"]', title: 'Save Your Savings Entry', text: 'Click this button to record your savings entry and update your savings records.' },
        { target: '[data-guide="savings-history"]', title: 'Savings History', text: 'View your previous savings entries here and manage your recorded savings.' }
    ];

    var isGaragePage = document.querySelector('[data-guide="garage-overview"]') !== null;
    var isBuilderPage = document.querySelector('[data-guide="builder-existing-bike"]') !== null;
    var isMarketplacePage = document.getElementById('marketplaceSearchForm') !== null;
    var isExpensesPage = document.querySelector('[data-guide="expenses-overview"]') !== null;
    var isSavingsPage = document.querySelector('[data-guide="savings-overview"]') !== null;

    var steps = isGaragePage ? garageSteps : (isBuilderPage ? builderSteps : (isMarketplacePage ? marketplaceSteps : (isExpensesPage ? expensesSteps : (isSavingsPage ? savingsSteps : []))));

    var availableSteps = [];
    var currentStep = 0;
    var highlightedElement = null;
    var originalScreen = null;
    var originalProgress = null;

    function removeHighlight() {
        if (highlightedElement) {
            highlightedElement.classList.remove('user-guide-highlight');
            highlightedElement = null;
        }
    }

    function rememberCurrentScreen() {
        var activeScreen = document.querySelector('.builder-screen.active:not([hidden])') || document.querySelector('.builder-screen:not([hidden])');
        var activeProgress = document.querySelector('.builder-progress-step.active');
        originalScreen = activeScreen ? activeScreen.id : null;
        originalProgress = activeProgress ? activeProgress.getAttribute('data-progress') : null;
    }

    function showBuilderScreen(screenName) {
        if (!screenName) {
            return;
        }
        var screens = document.querySelectorAll('.builder-screen');
        var progressSteps = document.querySelectorAll('.builder-progress-step');
        Array.prototype.forEach.call(screens, function (screen) {
            var isActive = screen.getAttribute('data-step') === screenName;
            screen.hidden = !isActive;
            screen.classList.toggle('active', isActive);
        });
        Array.prototype.forEach.call(progressSteps, function (step) {
            step.classList.toggle('active', step.getAttribute('data-progress') === screenName);
        });
    }

    function restoreBuilderScreen() {
        if (!originalScreen) {
            return;
        }
        var screen = document.getElementById(originalScreen);
        if (screen) {
            showBuilderScreen(screen.getAttribute('data-step'));
        }
        if (originalProgress) {
            var progressStep = document.querySelector('.builder-progress-step[data-progress="' + originalProgress + '"]');
            if (progressStep) {
                Array.prototype.forEach.call(document.querySelectorAll('.builder-progress-step'), function (step) {
                    step.classList.toggle('active', step === progressStep);
                });
            }
        }
        originalScreen = null;
        originalProgress = null;
    }

    function closeGuide() {
        removeHighlight();
        panel.hidden = true;
        backdrop.hidden = true;
        document.body.classList.remove('user-guide-active');
        if (isBuilderPage) {
            restoreBuilderScreen();
        }
        currentStep = 0;
    }

    function showStep(index) {
        if (index < 0 || index >= availableSteps.length) {
            closeGuide();
            return;
        }
        removeHighlight();
        currentStep = index;
        var step = availableSteps[currentStep];

        if (isBuilderPage) {
            showBuilderScreen(step.screen);
        }

        highlightedElement = document.querySelector(step.target);

        if (!highlightedElement) {
            closeGuide();
            return;
        }

        highlightedElement.classList.add('user-guide-highlight');
        highlightedElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        title.textContent = step.title;
        description.textContent = step.text;
        count.textContent = 'HOW TO USE';
        counter.textContent = 'Step ' + (currentStep + 1) + ' of ' + availableSteps.length;
        progress.style.width = ((currentStep + 1) / availableSteps.length * 100) + '%';
        previousButton.disabled = currentStep === 0;
        nextButton.textContent = currentStep === availableSteps.length - 1 ? 'Finish' : 'Next';
    }

    function startGuide() {
        availableSteps = steps.filter(function (step) {
            return document.querySelector(step.target) !== null;
        });

        if (!availableSteps.length) {
            return;
        }

        if (isBuilderPage) {
            rememberCurrentScreen();
        }

        panel.hidden = false;
        backdrop.hidden = false;
        document.body.classList.add('user-guide-active');
        showStep(0);
    }

    startButton.addEventListener('click', startGuide);

    previousButton.addEventListener('click', function () {
        if (currentStep > 0) {
            showStep(currentStep - 1);
        }
    });

    nextButton.addEventListener('click', function () {
        if (currentStep < availableSteps.length - 1) {
            showStep(currentStep + 1);
        } else {
            closeGuide();
        }
    });

    closeButton.addEventListener('click', closeGuide);
    backdrop.addEventListener('click', closeGuide);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !panel.hidden) {
            closeGuide();
        }
    });
});
