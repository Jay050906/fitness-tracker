// Check cookies
document.addEventListener("DOMContentLoaded", function() {
    // Cookie greeting
    var userCookie = getCookie("user");
    var welcomeEl = document.getElementById("welcome-cookie");
    if (welcomeEl && userCookie) {
        welcomeEl.innerText = "Welcome back, " + userCookie + "!";
    }

    // Auto draft
    var userField = document.getElementById("username");
    if (userField && localStorage.getItem("draft_user")) {
        userField.value = localStorage.getItem("draft_user");
    }
});

// Draft save
function saveDraft() {
    var val = document.getElementById("username").value;
    localStorage.setItem("draft_user", val);
}

// Read cookie
function getCookie(name) {
    var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
    return match ? match[2] : null;
}

// Auth check
function validateAuth(event) {
    event.preventDefault();
    var u = document.getElementById("username").value;
    var p = document.getElementById("password").value;
    var err = document.getElementById("error-msg");

    // Simple check
    if (u.trim() === "" || p.trim() === "") {
        err.innerText = "Fields required";
        return false;
    }

    // Call API
    fetch("api.php?action=login", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ username: u, password: p })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.status === "success") {
            window.location.href = "dashboard.html";
        } else {
            err.innerText = data.message;
        }
    })
    .catch(function() {
        err.innerText = "Server error";
    });
    return false;
}

// Draw chart
function drawChart(logs) {
    var canvas = document.getElementById("weightChart");
    if (!canvas) return;
    var ctx = canvas.getContext("2d");
    
    // Clear canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    if (!logs || logs.length === 0) return;

    // Set style
    ctx.strokeStyle = "#4f46e5";
    ctx.lineWidth = 2;
    ctx.beginPath();

    // Map points
    var padding = 30;
    var step = (canvas.width - padding * 2) / (logs.length > 1 ? logs.length - 1 : 1);

    logs.forEach(function(item, index) {
        var x = padding + index * step;
        var y = canvas.height - padding - (parseFloat(item.weight) * 1.5 - 50);

        if (index === 0) {
            ctx.moveTo(x, y);
        } else {
            ctx.lineTo(x, y);
        }
    });

    ctx.stroke();
}

// Calc BMI
function calculateBMIScore(weight, height) {
    if (!weight || !height || height <= 0) return { score: 0, status: "N/A", badge: "bg-secondary" };
    var hMeter = height / 100;
    var bmi = (weight / (hMeter * hMeter)).toFixed(1);

    if (bmi < 18.5) return { score: bmi, status: "Underweight", badge: "bg-warning" };
    if (bmi < 24.9) return { score: bmi, status: "Normal", badge: "bg-success" };
    if (bmi < 29.9) return { score: bmi, status: "Overweight", badge: "bg-danger" };
    return { score: bmi, status: "Obese", badge: "bg-dark" };
}

// Calc Nutrition
function calculateDietPlan(weight, height) {
    if (!weight || !height) return { calories: 2000, protein: 120, carbs: 220, fats: 65 };
    var bmr = 10 * weight + 6.25 * height - 5 * 25 + 5;
    var tdee = Math.round(bmr * 1.375);
    return {
        calories: tdee,
        protein: Math.round(weight * 2),
        carbs: Math.round((tdee * 0.45) / 4),
        fats: Math.round((tdee * 0.25) / 9)
    };
}

// Future Weight
function projectWeightInFourWeeks(currentWeight, dailyCal, targetCal) {
    if (!currentWeight) return currentWeight;
    var diff = (dailyCal - targetCal) * 28;
    var weightChange = diff / 7700;
    return (parseFloat(currentWeight) + weightChange).toFixed(1);
}

// Weekly Plan
function getWeeklyExercises(status) {
    if (status === "Underweight") {
        return [
            { day: "Mon", title: "Strength Training", detail: "Squats, Bench Press (45 mins)" },
            { day: "Tue", title: "Hypertrophy", detail: "Dumbbell Rows, Shoulder Press" },
            { day: "Wed", title: "Rest Day", detail: "Stretching & Yoga" },
            { day: "Thu", title: "Leg Workout", detail: "Leg Press, Lunges" },
            { day: "Fri", title: "Upper Body", detail: "Pullups, Pushups, Core" },
            { day: "Sat", title: "Light Cardio", detail: "Brisk Walk (30 mins)" },
            { day: "Sun", title: "Rest & Recovery", detail: "Full Body Massage" }
        ];
    } else if (status === "Overweight" || status === "Obese") {
        return [
            { day: "Mon", title: "HIIT Cardio", detail: "Jumping Jacks, Treadmill (40 mins)" },
            { day: "Tue", title: "Full Body Circuit", detail: "Kettlebell Swings, Bodyweight Squats" },
            { day: "Wed", title: "Active Recovery", detail: "Walking (45 mins)" },
            { day: "Thu", title: "Cycling & Core", detail: "Stationary Bike, Planks" },
            { day: "Fri", title: "Lower Body", detail: "Deadlifts, Step-ups" },
            { day: "Sat", title: "Cardio Blast", detail: "Rowing Machine, Skipping" },
            { day: "Sun", title: "Rest Day", detail: "Light Mobility Exercises" }
        ];
    } else {
        return [
            { day: "Mon", title: "Upper Body", detail: "Pushups, Dumbbell Press (40 mins)" },
            { day: "Tue", title: "Cardio Session", detail: "Running / Jogging (30 mins)" },
            { day: "Wed", title: "Core & Abs", detail: "Planks, Crunches, Leg Raises" },
            { day: "Thu", title: "Lower Body", detail: "Squats, Romanian Deadlifts" },
            { day: "Fri", title: "Flexibility", detail: "Pilates / Yoga Flow" },
            { day: "Sat", title: "Outdoor Sport", detail: "Swimming / Cycling / Football" },
            { day: "Sun", title: "Rest Day", detail: "Active Recovery Walk" }
        ];
    }
}
