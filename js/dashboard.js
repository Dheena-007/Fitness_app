// js/dashboard.js (Final PHP Version)

document.addEventListener('DOMContentLoaded', function() {
    
    // --- ELEMENT REFERENCES ---
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
    function fetchData(url, options = {}) {
        return fetch(url, options).then(response => {
            if (!response.ok) {
                return response.text().then(text => { 
                    throw new Error(`Network response was not ok for ${url}. Status: ${response.status}. Response: ${text}`);
                });
            }
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.indexOf("application/json") !== -1) {
                return response.json();
            }
            return {};
        });
    }

    // --- EVENT LISTENERS (with safety checks) ---
    if (metricsForm) metricsForm.addEventListener('submit', handleMetricsSubmit);
    if (foodLogForm) foodLogForm.addEventListener('submit', handleFoodLogSubmit);
    if (addWaterBtn) addWaterBtn.addEventListener('click', handleWaterLogSubmit);
    if (calorieGoalForm) calorieGoalForm.addEventListener('submit', handleCalorieGoalSubmit);
    if (goalForm) goalForm.addEventListener('submit', handleGoalSubmit);
    if (recipeSearchForm) recipeSearchForm.addEventListener('submit', handleRecipeSearch);

    // --- HANDLER FUNCTIONS ---
    
    // ... (உங்கள் dashboard.js கோப்பின் மற்ற செயல்பாடுகளுக்கு இடையில் இதைச் சேர்க்கவும்)

/**
 * "Log Your Daily Metrics" படிவத்தைக் கையாள்கிறது.
 * இது BMI-ஐக் கணக்கிட்டு, உடனடி AI பரிந்துரையை உருவாக்கி,
 * பின்னர் தரவைச் சேமிக்க API-ஐ அழைக்கிறது.
 */
function handleMetricsSubmit(event) {
    event.preventDefault(); // பக்கம் Refresh ஆவதைத் தடுக்கிறது
    
    const height = document.getElementById('height').value;
    const weight = document.getElementById('weight').value;
    const activity = document.getElementById('activity').value;

    // --- இதுதான் நீங்கள் கேட்ட AI கணக்கீடு மற்றும் பரிந்துரை தர்க்கம் ---
    let quickRecommendationText = '';
    if (height > 0 && weight > 0) {
        const heightInMeters = height / 100;
        const bmi = weight / (heightInMeters * heightInMeters);

        // BMI அடிப்படையில் உணவு மற்றும் உடற்பயிற்சி பரிந்துரையை உருவாக்குகிறது
        if (bmi < 18.5) {
            quickRecommendationText = 'AI Tip: Focus on a calorie surplus & strength training.';
        } else if (bmi < 25) {
            quickRecommendationText = 'AI Tip: Focus on maintenance & a balanced fitness routine.';
        } else {
            quickRecommendationText = 'AI Tip: Focus on a calorie deficit & regular cardio.';
        }
    }
    // -----------------------------------------------------------------

    const payload = { height_cm: height, weight_kg: weight, activity_level: activity };
    const options = { 
        method: 'POST', 
        headers: { 'Content-Type': 'application/json' }, 
        body: JSON.stringify(payload) 
    };

    // api/metrics.php கோப்பிற்கு தரவை அனுப்புகிறது
    fetchData('api/metrics.php', options)
        .then(data => {
            // வெற்றி பெற்றால், சேமிக்கப்பட்ட செய்தியையும், AI பரிந்துரையையும் காட்டுகிறது
            formMessage.innerHTML = `${lang_js.metrics_saved}<br><strong style="color: #34495e;">${quickRecommendationText}</strong>`;
            formMessage.style.color = 'green';
            
            // மற்ற எல்லா கார்டுகளையும் Refresh செய்கிறது
            loadAllData(); 
        })
        .catch(error => {
            // தோல்வியுற்றால், பிழைச் செய்தியைக் காட்டுகிறது
            formMessage.textContent = lang_js.error_saving;
            formMessage.style.color = 'red';
            console.error('Error saving metrics:', error);
        });
}

// ... (உங்கள் dashboard.js கோப்பின் மற்ற செயல்பாடுகள், loadAllData() போன்றவை)

    function handleFoodLogSubmit(event) {
        event.preventDefault();
        const payload = {
            food_name: document.getElementById('foodName').value,
            calories: document.getElementById('foodCalories').value
        };
        const options = { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) };

        fetchData('api/food_log.php', options)
            .then(() => {
                updateCalorieTracker(); 
                loadSmartRecommendations(); 
                foodLogForm.reset();
            })
            .catch(error => console.error('Error saving food log:', error));
    }

    function handleWaterLogSubmit() {
        fetchData('api/water_log.php', { method: 'POST' })
            .then(() => loadWaterLog())
            .catch(error => console.error('Error saving water log:', error));
    }

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

    function handleGoalSubmit(e) {
         e.preventDefault();
         const payload = {
             age: document.getElementById('userAge').value,
             gender: document.getElementById('userGender').value,
             goal: document.getElementById('userGoal').value
         };
         const options = { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) };
         
         fetchData('api/predictions.php?action=calculate_calories', options) 
             .then(() => {
                 goalMessage.textContent = "Goal updated successfully!";
                 goalMessage.style.color = 'green';
                 loadSmartRecommendations();
                 loadAutoCalorieGoal();
             })
             .catch(error => console.error('Error updating goal:', error));
    }

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

    // --- DATA LOADING AND UI UPDATE FUNCTIONS (with safety checks) ---
    
    // js/dashboard.js файலில்...

/**
 * கலோரி டிராக்கரைப் புதுப்பிக்கிறது. இது AI இலக்கைப் பெற்று,
 * இன்று பதிவுசெய்த உணவுப் பட்டியலைக் காட்டுகிறது.
 */
function updateCalorieTracker() {
    // இரண்டு HTML கூறுகளும் உள்ளனவா எனச் சரிபார்க்கவும்
    if (!foodList || !calorieProgressBar || !calorieProgressText) return; 

    // AI இலக்கு மற்றும் உணவுப் பதிவு இரண்டையும் ஒரே நேரத்தில் பெறுகிறது
    Promise.all([
        fetchData('api/predictions.php?action=get_auto_calorie_goal'),
        fetchData('api/food_log.php') // <-- இது உங்கள் உணவுப் பட்டியலைப் பெறுகிறது
    ])
    .then(([goalData, logData]) => {
        const calorieGoal = goalData.auto_calorie_goal || 2000;
        let consumedCalories = 0;

        // --- இதுதான் உங்கள் பட்டியலை உருவாக்கும் முக்கிய பகுதி ---
        foodList.innerHTML = ''; // பழைய பட்டியலை அழிக்கிறது
        
        // logData (JSON array) இல் உள்ள ஒவ்வொரு பொருளுக்கும் (item) ஒரு லூப் இயங்குகிறது
        logData.forEach(item => {
            const li = document.createElement('li'); // ஒரு புதிய <li> தனிமத்தை உருவாக்குகிறது
            li.textContent = `${item.food_name} - ${item.calories} kcal`; // e.g., "Apple - 2 kcal"
            foodList.appendChild(li); // அதை பட்டியலின் கீழே சேர்க்கிறது
            
            consumedCalories += parseInt(item.calories);
        });
        // ------------------------------------------

        // புரோகிரெஸ் பார் மற்றும் மொத்த கலோரிகளைப் புதுப்பிக்கிறது
        const percentage = calorieGoal > 0 ? (consumedCalories / calorieGoal) * 100 : 0;
        calorieProgressBar.style.width = `${Math.min(percentage, 100)}%`;
        calorieProgressText.textContent = `${consumedCalories} / ${calorieGoal} kcal`;

    }).catch(error => console.error('Error updating calorie tracker:', error));
}

// ... (மற்ற செயல்பாடுகள்) ...

// --- INITIAL DATA LOAD ---
// பக்கம் ஏற்றப்படும்போது (load) இந்தச் செயல்பாடு அழைக்கப்படுவதை உறுதிப்படுத்தவும்
updateCalorieTracker();

    function loadWeeklySummary() {
        if (weeklySummaryEl) fetchData('api/weekly_summary.php').then(data => {
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

    function fetchMetricsAndDrawChart() {
        if (progressChartCanvas) fetchData('api/metrics.php').then(data => {
            const labels = data.map(record => new Date(record.recorded_at).toLocaleDateString());
            const weights = data.map(record => record.weight_kg);
            drawChart(labels, weights);
        }).catch(error => console.error('Could not fetch chart data:', error));
    }

    function loadSmartRecommendations() {
        if (smartRecommendationsEl) fetchData('api/smart_recommendations.php').then(data => {
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

    function loadWeightPrediction() {
        if (predictedWeightEl) fetchData('api/predictions.php?action=predict_weight').then(data => {
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

    function loadWaterLog() {
        if (waterCountEl) fetchData('api/water_log.php').then(data => { 
            waterCountEl.textContent = data.glasses || 0; 
        }).catch(error => console.error('Could not load water log:', error));
    }

    function loadAutoCalorieGoal() {
        if (autoCalorieGoalEl) fetchData('api/predictions.php?action=get_auto_calorie_goal').then(data => {
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

    function loadAiInsight() {
        if (aiInsightEl) fetchData('api/ai_insights.php').then(data => { 
            aiInsightEl.textContent = data.insight || 'Keep up the great work!'; 
        }).catch(error => {
            aiInsightEl.textContent = 'Could not load insight.';
            console.error('Error fetching AI insight:', error);
        });
    }

    function drawChart(labels, data) {
        if (!progressChartCanvas) return;
        const ctx = progressChartCanvas.getContext('2d');
        if (progressChart) progressChart.destroy(); 
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
    loadAllData();

    // --- Pre-fill forms using userProfile from PHP ---
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