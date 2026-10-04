// Module init
var app = angular.module("fitnessApp", []);

// Controller logic
app.controller("TrackerController", function($scope, $http) {
    $scope.logs = [];
    $scope.newLog = { height: 170 };
    $scope.searchDate = "";
    
    $scope.bmiInfo = { score: 0, status: "N/A", badge: "bg-secondary" };
    $scope.nutrition = { calories: 2000, protein: 120, carbs: 220, fats: 65 };
    $scope.weeklyPlan = [];
    $scope.projectedWeight = 0;

    // Fetch records
    $scope.loadLogs = function() {
        $http.get("api.php?action=get_logs")
            .then(function(response) {
                if (Array.isArray(response.data)) {
                    $scope.logs = response.data;
                    $scope.updateMetrics();
                    
                    // Render graph
                    setTimeout(function() {
                        drawChart($scope.logs);
                    }, 100);
                } else if (response.data && response.data.status === "error") {
                    console.log("Database/API Error:", response.data.message);
                    $scope.logs = [];
                }
            })
            .catch(function(err) {
                console.log("Error loading logs");
                $scope.logs = [];
            });
    };

    // Update stats
    $scope.updateMetrics = function() {
        if (!Array.isArray($scope.logs) || $scope.logs.length === 0) return;
        var latest = $scope.logs[$scope.logs.length - 1];
        
        var w = parseFloat(latest.weight);
        var h = parseFloat(latest.height) || 170;
        var cal = parseInt(latest.calories);

        $scope.bmiInfo = calculateBMIScore(w, h);
        $scope.nutrition = calculateDietPlan(w, h);
        $scope.weeklyPlan = getWeeklyExercises($scope.bmiInfo.status);
        $scope.projectedWeight = projectWeightInFourWeeks(w, cal, $scope.nutrition.calories);
    };

    // Add entry
    $scope.addLog = function() {
        // Format date
        if ($scope.newLog.log_date instanceof Date) {
            $scope.newLog.log_date = $scope.newLog.log_date.toISOString().split('T')[0];
        }

        $http.post("api.php?action=add_log", $scope.newLog)
            .then(function(response) {
                if (response.data.status === "success") {
                    var lastHeight = $scope.newLog.height;
                    $scope.newLog = { height: lastHeight };
                    $scope.loadLogs();
                }
            });
    };

    // Load initial
    $scope.loadLogs();
});
