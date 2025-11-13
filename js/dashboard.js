// js/dashboard.js (Final PHP Version)

document.addEventListener('DOMContentLoaded', function() {
    
    // --- ELEMENT REFERENCES ---
    // Get all elements needed for the dashboard
    const metricsForm = document.getElementById('metricsForm');
    const formMessage = document.getElementById('formMessage');
    const progressChartCanvas = document.getElementById('progressChart');
    const foodLogForm = document.getElementById('foodLogForm');
    const foodList = document.getElementById('foodList');
    const totalCaloriesEl = document.getElementById('totalCalories');
    const calorieProgressBar = document.getElementById('calorieProgressBar');
    const calorieProgressText = document.getElementById('calorieProgressText');
    const addWaterBtn = document.getElementById('addWaterBtn');
    const waterCountEl = document.getElementById('waterCount');
    const predictedWeightEl = document.getElementById('predictedWeight');
    const calorieGoalForm = document.getElementById('calorieGoalForm');
    const calorieGoalResultEl = document.getElementById('calorieGoalResult');
    const goalForm = document.getElementById('goalForm');
    const goalMessage = document.getElementById('goalMessage');
    const smartRecommendationsEl = document.getElementById('smartRecommendations');
    const weeklySummaryEl = document.getElementById('weeklySummaryContent');
    const autoCalorieGoalEl = document.getElementById('autoCalorieGoal');
    const aiInsightEl = document.getElementById('aiInsightText');
    const recipeSearchForm = document.getElementById('recipeSearchForm');
    const recipeSearchResultsEl = document.getElementById('recipeSearchResults');
    
    let progressChart = null; // Holds the chart instance

    // --- UNIVERSAL FETCH FUNCTION ---
    /**
     * Fetches data from a URL and returns the JSON response.
     * @param {string} url - The API endpoint URL (e.g., 'api/metrics.php')
     * @param {object} options - Optional fetch options (method, body, headers)
     * @returns {Promise<object>} - A promise that resolves with the JSON data
     */
    function fetchData(url, options = {}) {
        return fetch(url, options).then(response => {
            if (!response.ok) {
                // If response is not ok, try to get text error from server
                return response.text().then(text => { 
                    throw new Error(`Network response was not ok for ${url}. Status: ${response.status}. Response: ${text}`);
                });
            }
            // Check if response is JSON, otherwise return empty object
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.indexOf("application/json") !== -1) {
                return response.json();
            }
            return {};
        });
    }

    // --- EVENT LISTENERS (with safety checks) ---
    // Only add listeners if the elements actually exist on the page
    if (metricsForm) metricsForm.addEventListener('submit', handleMetricsSubmit);
    if (foodLogForm) foodLogForm.addEventListener('submit', handleFoodLogSubmit);
    if (addWaterBtn) addWaterBtn.addEventListener('click', handleWaterLogSubmit);
    if (calorieGoalForm) calorieGoalForm.addEventListener('submit', handleCalorieGoalSubmit);
    if (goalForm) goalForm.addEventListener('submit', handleGoalSubmit);
    if (recipeSearchForm) recipeSearchForm.addEventListener('submit', handleRecipeSearch);

    // --- HANDLER FUNCTIONS ---
    
    /**
     * Handles the "Log Your Daily Metrics" form submission.
     * Saves data and provides an instant AI tip based on BMI.
     */
    function handleMetricsSubmit(event) {
        event.preventDefault();
        const height = document.getElementById('height').value;
        const weight = document.getElementById('weight').value;
        const activity = document.getElementById('activity').value;

        // Instant client-side AI tip
        let quickRecommendationText = '';
        if (height > 0 && weight > 0) {
            const heightInMeters = height / 100;
            const bmi = weight / (heightInMeters * heightInMeters);
            if (bmi < 18.5) { quickRecommendationText = 'AI Tip: Focus on a calorie surplus & strength training.'; }
            else if (bmi < 25) { quickRecommendationText = 'AI Tip: Focus on maintenance & a balanced fitness routine.'; }
            else { quickRecommendationText = 'AI Tip: Focus on a calorie deficit & regular cardio.'; }
        }

        const payload = { height_cm: height, weight_kg: weight, activity_level: activity };
        const options = { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) };

        fetchData('api/metrics.php', options)
            .then(data => {
                formMessage.innerHTML = `${lang_js.metrics_saved}<br><strong style="color: #34495e;">${quickRecommendationText}</strong>`;
                formMessage.style.color = 'green';
                loadAllData(); // Refresh all dashboard data
            })
            .catch(error => {
                formMessage.textContent = lang_js.error_saving;
                formMessage.style.color = 'red';
                console.error('Error saving metrics:', error);
            });
    }

    /**
     * Handles the "Daily Calorie Tracker" form submission.
     */
    function handleFoodLogSubmit(event) {
        event.preventDefault();
        const payload = {
            food_name: document.getElementById('foodName').value,
            calories: document.getElementById('foodCalories').value
        };
        const options = { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) };

        fetchData('api/food_log.php', options)
            .then(() => {
                updateCalorieTracker(); // Refresh calorie progress bar
                loadSmartRecommendations(); // Refresh AI recommendations
                foodLogForm.reset();
            })
            .catch(error => console.error('Error saving food log:', error));
    }

    /**
     * Handles the "Add a Glass" button click for water intake.
     */
    function handleWaterLogSubmit() {
        fetchData('api/water_log.php', { method: 'POST' })
            .then(() => loadWaterLog())
            .catch(error => console.error('Error saving water log:', error));
    }

    /**
     * Handles the "Calorie Goal Calculator" form submission.
     */
    function handleCalorieGoalSubmit(e) {
        e.preventDefault();
        const payload = {
            age: document.getElementById('userAge').value,
            gender: document.getElementById('userGender').value,
            target_weight: document.getElementById('targetWeight').value,
            target_date: document.getElementById('targetDate').value,
        };
        const options = { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) };

        fetchData('api/predictions.php?action=calculate_calories', options)
            .then(data => {
                if (data.calorie_goal) {
                    calorieGoalResultEl.innerHTML = `<p style="font-weight: bold;">To reach your goal, your recommended daily intake is: <span style="font-size: 1.5rem; color: var(--primary-color);">${data.calorie_goal} kcal</span></p>`;
                } else {
                    calorieGoalResultEl.innerHTML = `<p class="alert alert-danger">${data.message || 'Could not calculate.'}</p>`;
                }
            })
            .catch(error => console.error('Error calculating calorie goal:', error));
    }

    /**
     * Handles the "Set Your Primary Goal" form submission.
     */
    function handleGoalSubmit(e) {
         e.preventDefault();
         const payload = {
             age: document.getElementById('userAge').value,
             gender: document.getElementById('userGender').value,
             goal: document.getElementById('userGoal').value
         };
         const options = { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) };
         
         // This API action also saves Age and Gender
         fetchData('api/predictions.php?action=calculate_calories', options) 
             .then(() => {
                 goalMessage.textContent = "Goal updated successfully!";
                 goalMessage.style.color = 'green';
                 loadSmartRecommendations();
                 loadAutoCalorieGoal();
             })
             .catch(error => console.error('Error updating goal:', error));
    }

    /**
     * Handles the "Recipe Finder" form submission.
     */
    function handleRecipeSearch(event) {
        event.preventDefault();
        const query = document.getElementById('recipeSearchQuery').value;
        const calories = document.getElementById('recipeMaxCalories').value;
        
        let queryString = new URLSearchParams();
        if (query) queryString.append('query', query);
        if (calories) queryString.append('calories', calories);

        recipeSearchResultsEl.innerHTML = '<p>Searching...</p>';

        fetchData(`api/search_recipes.php?${queryString.toString()}`)
            .then(data => {
                if (data.error) throw new Error(data.error);
                if (data.length === 0) {
                    recipeSearchResultsEl.innerHTML = '<p>No recipes found.</p>';
                    return;
                }
                let html = '<ul style="list-style: none; padding: 0;">';
                data.forEach(recipe => {
                    html += `<li style="border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 10px;">
                        <strong>${recipe.recipe_name}</strong> (~${recipe.calories_per_serving} kcal)
                        <details><summary style="cursor: pointer; font-size: 0.9rem; color: var(--primary-color);">View Instructions</summary>
                        <p style="font-size: 0.9rem; margin-top: 5px;">${recipe.instructions}</p>
                        </details>
                    </li>`;
                });
                recipeSearchResultsEl.innerHTML = html + '</ul>';
            })
            .catch(error => {
                recipeSearchResultsEl.innerHTML = '<p class="alert alert-danger">An error occurred.</p>';
                console.error('Error searching recipes:', error);
            });
    }

    // --- DATA LOADING AND UI UPDATE FUNCTIONS ---

    /**
     * Updates the calorie progress bar by fetching the AI goal and today's logged food.
     */
    function updateCalorieTracker() {
        if (!foodList || !calorieProgressBar || !calorieProgressText) return; 
        Promise.all([
            fetchData('api/predictions.php?action=get_auto_calorie_goal'),
            fetchData('api/food_log.php')
        ])
        .then(([goalData, logData]) => {
            const calorieGoal = goalData.auto_calorie_goal || 2000;
            let consumedCalories = 0;
            foodList.innerHTML = ''; // Clear old list
            logData.forEach(item => {
                const li = document.createElement('li');
                li.textContent = `${item.food_name} - ${item.calories} kcal`;
                foodList.appendChild(li);
                consumedCalories += parseInt(item.calories);
            });
            const percentage = calorieGoal > 0 ? (consumedCalories / calorieGoal) * 100 : 0;
            calorieProgressBar.style.width = `${Math.min(percentage, 100)}%`;
            calorieProgressText.textContent = `${consumedCalories} / ${calorieGoal} kcal`;
        }).catch(error => console.error('Error updating calorie tracker:', error));
    }

    /**
     * Loads the 7-day summary data.
     */
    function loadWeeklySummary() {
        if (weeklySummaryEl) {
            fetchData('api/weekly_summary.php').then(data => {
                let weightChangeText = `${data.weight_change} kg`;
                let weightChangeClass = '';
                if (data.weight_change > 0) { weightChangeText = `+${data.weight_change} kg`; weightChangeClass = 'text-danger'; }
                else if (data.weight_change < 0) { weightChangeClass = 'text-success'; }
                weeklySummaryEl.innerHTML = `
                    <ul style="list-style: none; padding: 0; font-size: 1.1rem;">
                        <li style="margin-bottom: 10px;"><strong>Avg. Daily Calories:</strong> ${data.avg_calories} kcal</li>
                        <li style="margin-bottom: 10px;"><strong>Avg. Daily Water:</strong> ${data.avg_glasses} glasses</li>
                        <li style="margin-bottom: 10px;"><strong>Weight Change (7d):</strong> <span class="${weightChangeClass}">${weightChangeText}</span></li>
                        <li style="margin-bottom: 10px;"><strong>Workouts This Week:</strong> ${data.workout_days} / 7 days</li>
                    </ul>`;
            }).catch(error => {
                weeklySummaryEl.innerHTML = '<p>Could not load summary.</p>';
                console.error('Error fetching weekly summary:', error);
            });
        }
    }

    /**
     * Fetches weight history and draws the progress chart.
     */
    function fetchMetricsAndDrawChart() {
        if (progressChartCanvas) {
            fetchData('api/metrics.php').then(data => {
                const labels = data.map(record => new Date(record.recorded_at).toLocaleDateString());
                const weights = data.map(record => record.weight_kg);
                drawChart(labels, weights);
            }).catch(error => console.error('Could not fetch chart data:', error));
        }
    }

    /**
     * Loads the smart AI recommendations (food & exercise).
     */
    function loadSmartRecommendations() {
        if (smartRecommendationsEl) {
            fetchData('api/smart_recommendations.php').then(data => {
                if (data.error) {
                    smartRecommendationsEl.innerHTML = `<p class="alert alert-danger">${data.error}</p>`;
                    return;
                }
                let html = `<h5>Calorie Analysis</h5><p>Goal: ${data.calorie_analysis.goal} | Consumed: ${data.calorie_analysis.consumed} | Remaining: ${data.calorie_analysis.remaining}</p>`;
                html += `<h5>Food Suggestions</h5>`;
                if (data.food_suggestions && data.food_suggestions.length > 0) {
                    html += '<ul>';
                    data.food_suggestions.forEach(food => html += `<li>${food.recipe_name} (~${food.calories_per_serving} kcal)</li>`);
                    html += '</ul>';
                } else {
                    html += '<p>No food suggestions for your remaining calories.</p>';
                }
                html += `<hr><h5>Exercise Plan (Goal: ${data.exercise_plan.focus})</h5><p>${data.exercise_plan.plan}</p>`;
                smartRecommendationsEl.innerHTML = html;
            }).catch(error => {
                smartRecommendationsEl.innerHTML = '<p class="alert alert-danger">Could not load AI analysis.</p>';
                console.error('Error fetching smart recommendations:', error);
            });
        }
    }

    /**
     * Loads the AI weight prediction.
     */
    function loadWeightPrediction() {
        if (predictedWeightEl) {
            fetchData('api/predictions.php?action=predict_weight').then(data => {
                if (data.predicted_weight) {
                    predictedWeightEl.textContent = `${data.predicted_weight} kg`;
                } else {
                    predictedWeightEl.textContent = 'Not enough data.';
                }
            }).catch(error => {
                predictedWeightEl.textContent = 'Error.';
                console.error('Error fetching weight prediction:', error);
            });
        }
    }

    /**
     * Loads the current water intake.
     */
    function loadWaterLog() {
        if (waterCountEl) {
            fetchData('api/water_log.php').then(data => { 
                waterCountEl.textContent = data.glasses || 0; 
            }).catch(error => console.error('Could not load water log:', error));
        }
    }

    /**
     * Loads the automatic AI calorie goal.
     */
    function loadAutoCalorieGoal() {
        if (autoCalorieGoalEl) {
            fetchData('api/predictions.php?action=get_auto_calorie_goal').then(data => {
                if (data.auto_calorie_goal) {
                    autoCalorieGoalEl.textContent = data.auto_calorie_goal;
                } else {
                    autoCalorieGoalEl.textContent = 'N/A';
                    if(data.error) console.error('AutoCalorieGoal Error:', data.error);
                }
            }).catch(error => {
                autoCalorieGoalEl.textContent = 'Error';
                console.error('Error fetching auto calorie goal:', error);
            });
        }
    }

    /**
     * Loads the AI motivational insight.
     */
    function loadAiInsight() {
        if (aiInsightEl) {
            fetchData('api/ai_insights.php').then(data => { 
                aiInsightEl.textContent = data.insight || 'Keep up the great work!'; 
            }).catch(error => {
                aiInsightEl.textContent = 'Could not load insight.';
                console.error('Error fetching AI insight:', error);
            });
        }
    }

    /**
     * Helper function to draw the Chart.js chart.
     */
    function drawChart(labels, data) {
        if (!progressChartCanvas) return;
        const ctx = progressChartCanvas.getContext('2d');
        if (progressChart) {
            progressChart.destroy(); // Destroy old chart before drawing new one
        }
        progressChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Weight (kg)',
                    data: data,
                    borderColor: 'var(--primary-color)',
                    backgroundColor: 'rgba(0, 167, 157, 0.1)',
                    fill: true,
                    tension: 0.1
                }]
            }
        });
    }
    
    /**
     * Master function to load all data on page load or refresh.
     */
    function loadAllData() {
        loadWeeklySummary();
        fetchMetricsAndDrawChart();
        loadSmartRecommendations();
        updateCalorieTracker();
        loadWaterLog();
        loadWeightPrediction();
        loadAutoCalorieGoal();
        loadAiInsight();
    }

    // --- INITIAL DATA LOAD ---
    // Load all data when the page is ready
    loadAllData();

    // --- Pre-fill forms using userProfile from PHP ---
    // This uses the 'userProfile' constant defined in dashboard.php
    if (typeof userProfile !== 'undefined' && userProfile) {
        if (userProfile.age && document.getElementById('userAge')) {
            document.getElementById('userAge').value = userProfile.age;
        }
        if (userProfile.gender && document.getElementById('userGender')) {
            document.getElementById('userGender').value = userProfile.gender;
        }
        if (userProfile.goal && document.getElementById('userGoal')) {
            document.getElementById('userGoal').value = userProfile.goal;
        }
    }
});