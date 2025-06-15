<?php
// Claude API Integration Service

class ClaudeAnalysisService {
    private $api_key;
    private $base_url = 'https://api.anthropic.com/v1/messages';
    
    public function __construct($api_key) {
        $this->api_key = $api_key;
    }
    
    public function analyzeSessionPerformance($session_data, $questions_data) {
        if (!$this->api_key) {
            return null;
        }
        
        $prompt = $this->buildAnalysisPrompt($session_data, $questions_data);
        
        $response = $this->callClaudeAPI($prompt);
        
        if ($response) {
            return $this->parseAnalysisResponse($response);
        }
        
        return null;
    }
    
    public function generateStudyRecommendations($user_stats, $weak_areas) {
        if (!$this->api_key) {
            return null;
        }
        
        $prompt = $this->buildRecommendationPrompt($user_stats, $weak_areas);
        
        $response = $this->callClaudeAPI($prompt);
        
        if ($response) {
            return $this->parseRecommendationResponse($response);
        }
        
        return null;
    }
    
    public function analyzeQuestionPattern($questions_data) {
        if (!$this->api_key) {
            return null;
        }
        
        $prompt = $this->buildPatternPrompt($questions_data);
        
        $response = $this->callClaudeAPI($prompt);
        
        if ($response) {
            return $this->parsePatternResponse($response);
        }
        
        return null;
    }
    
    private function buildAnalysisPrompt($session_data, $questions_data) {
        $session_name = $session_data['test_name'];
        $total_questions = count($questions_data);
        $correct_count = array_sum(array_column($questions_data, 'is_correct'));
        $accuracy = $total_questions > 0 ? round(($correct_count / $total_questions) * 100, 1) : 0;
        
        // Analyze feelings distribution
        $feelings = [];
        foreach ($questions_data as $q) {
            $feelings[$q['feeling']] = ($feelings[$q['feeling']] ?? 0) + 1;
        }
        
        // Analyze subjects
        $subjects = [];
        foreach ($questions_data as $q) {
            if ($q['subject']) {
                $subjects[$q['subject']] = ($subjects[$q['subject']] ?? 0) + 1;
            }
        }
        
        $prompt = "Analyze this UPSC test performance data and provide insights:

**Test Session:** {$session_name}
**Total Questions:** {$total_questions}
**Accuracy:** {$accuracy}%

**Feelings Distribution:**
";
        
        foreach ($feelings as $feeling => $count) {
            $percentage = round(($count / $total_questions) * 100, 1);
            $prompt .= "- " . ucfirst(str_replace('_', ' ', $feeling)) . ": {$count} questions ({$percentage}%)\n";
        }
        
        if (!empty($subjects)) {
            $prompt .= "\n**Subject Distribution:**\n";
            foreach ($subjects as $subject => $count) {
                $percentage = round(($count / $total_questions) * 100, 1);
                $prompt .= "- {$subject}: {$count} questions ({$percentage}%)\n";
            }
        }
        
        $prompt .= "\nProvide a concise analysis focusing on:
1. Performance strengths and weaknesses
2. Confidence vs accuracy patterns
3. Subject-specific insights
4. Emotional state analysis
5. 3-4 specific actionable recommendations

Keep the response structured and under 300 words.";
        
        return $prompt;
    }
    
    private function buildRecommendationPrompt($user_stats, $weak_areas) {
        $prompt = "Based on this UPSC aspirant's performance data, provide personalized study recommendations:

**Overall Stats:**
- Total Questions Analyzed: {$user_stats['total_questions']}
- Overall Accuracy: {$user_stats['accuracy']}%
- Test Sessions: {$user_stats['sessions']}

**Weak Areas Identified:**
";
        
        foreach ($weak_areas as $area => $data) {
            $prompt .= "- {$area}: {$data['accuracy']}% accuracy ({$data['questions']} questions)\n";
        }
        
        $prompt .= "\nProvide:
1. 3-4 specific study strategies
2. Resource recommendations
3. Practice approaches
4. Time management tips
5. Confidence building suggestions

Focus on UPSC-specific preparation. Keep response under 250 words.";
        
        return $prompt;
    }
    
    private function buildPatternPrompt($questions_data) {
        $total = count($questions_data);
        
        // Analyze confidence vs accuracy
        $confidence_accuracy = [];
        foreach ($questions_data as $q) {
            if (isset($q['confidence_level']) && isset($q['is_correct'])) {
                $conf_range = $this->getConfidenceRange($q['confidence_level']);
                if (!isset($confidence_accuracy[$conf_range])) {
                    $confidence_accuracy[$conf_range] = ['correct' => 0, 'total' => 0];
                }
                $confidence_accuracy[$conf_range]['total']++;
                if ($q['is_correct']) {
                    $confidence_accuracy[$conf_range]['correct']++;
                }
            }
        }
        
        $prompt = "Analyze these test-taking patterns for a UPSC aspirant:

**Total Questions:** {$total}

**Confidence vs Accuracy Analysis:**
";
        
        foreach ($confidence_accuracy as $range => $data) {
            $acc = $data['total'] > 0 ? round(($data['correct'] / $data['total']) * 100, 1) : 0;
            $prompt .= "- {$range} confidence: {$acc}% accuracy ({$data['total']} questions)\n";
        }
        
        $prompt .= "\nIdentify:
1. Confidence calibration issues
2. Overconfidence or underconfidence patterns
3. Strategic insights for exam approach
4. Mental preparation recommendations

Provide practical, actionable insights under 200 words.";
        
        return $prompt;
    }
    
    private function getConfidenceRange($level) {
        if ($level <= 3) return "Low (1-3)";
        if ($level <= 6) return "Medium (4-6)";
        if ($level <= 8) return "High (7-8)";
        return "Very High (9-10)";
    }
    
    private function callClaudeAPI($prompt) {
        $headers = [
            'Content-Type: application/json',
            'x-api-key: ' . $this->api_key,
            'anthropic-version: 2023-06-01'
        ];
        
        $data = [
            'model' => 'claude-3-5-haiku-20241022',
            'max_tokens' => 18000,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->base_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            error_log('Claude API cURL error: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }
        
        curl_close($ch);
        
        if ($http_code !== 200) {
            error_log('Claude API HTTP error: ' . $http_code . ' - ' . $response);
            return null;
        }
        
        $decoded = json_decode($response, true);
        
        if (!$decoded || !isset($decoded['content'][0]['text'])) {
            error_log('Claude API response format error: ' . $response);
            return null;
        }
        
        return $decoded['content'][0]['text'];
    }
    
    private function parseAnalysisResponse($response) {
        return [
            'type' => 'session_analysis',
            'content' => trim($response),
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    private function parseRecommendationResponse($response) {
        return [
            'type' => 'study_recommendations',
            'content' => trim($response),
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    private function parsePatternResponse($response) {
        return [
            'type' => 'pattern_analysis',
            'content' => trim($response),
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
}

// Helper function to get profile's Claude API key
function getProfileClaudeAPI($pdo, $profile_id) {
    if (!$profile_id) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT claude_api_key FROM profiles WHERE id = ?");
    $stmt->execute([$profile_id]);
    $result = $stmt->fetch();
    
    return $result ? $result['claude_api_key'] : null;
}

// Generate AI insights for session
function generateSessionInsights($pdo, $session_id, $profile_id) {
    $api_key = getProfileClaudeAPI($pdo, $profile_id);
    
    if (!$api_key) {
        return null;
    }
    
    // Get session data
    $session = getSessionById($pdo, $session_id);
    $questions = getQuestionsBySession($pdo, $session_id);
    
    if (!$session || empty($questions)) {
        return null;
    }
    
    $claude = new ClaudeAnalysisService($api_key);
    return $claude->analyzeSessionPerformance($session, $questions);
}

// Generate AI recommendations for profile
function generateProfileRecommendations($pdo, $profile_id) {
    $api_key = getProfileClaudeAPI($pdo, $profile_id);
    
    if (!$api_key) {
        return null;
    }
    
    // Get profile stats
    $profile_stats = getProfileOverallStats($pdo, $profile_id);
    $weak_areas = getProfileWeakAreas($pdo, $profile_id);
    
    $claude = new ClaudeAnalysisService($api_key);
    return $claude->generateStudyRecommendations($profile_stats, $weak_areas);
}

function getProfileOverallStats($pdo, $profile_id) {
    $sessions = $pdo->prepare("SELECT COUNT(*) FROM test_sessions WHERE profile_id = ?");
    $sessions->execute([$profile_id]);
    $sessions = $sessions->fetchColumn();
    
    $questions = $pdo->prepare("SELECT COUNT(*) FROM question_logs ql JOIN test_sessions ts ON ql.session_id = ts.id WHERE ts.profile_id = ?");
    $questions->execute([$profile_id]);
    $questions = $questions->fetchColumn();
    
    $correct = $pdo->prepare("SELECT COUNT(*) FROM question_logs ql JOIN test_sessions ts ON ql.session_id = ts.id WHERE ts.profile_id = ? AND ql.is_correct = 1");
    $correct->execute([$profile_id]);
    $correct = $correct->fetchColumn();
    
    $accuracy = $questions > 0 ? round(($correct / $questions) * 100, 1) : 0;
    
    return [
        'sessions' => $sessions,
        'total_questions' => $questions,
        'accuracy' => $accuracy
    ];
}

function getProfileWeakAreas($pdo, $profile_id) {
    $stmt = $pdo->prepare("
        SELECT 
            subject,
            COUNT(*) as questions,
            SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct,
            ROUND((SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as accuracy
        FROM question_logs ql 
        JOIN test_sessions ts ON ql.session_id = ts.id 
        WHERE ts.profile_id = ? AND subject IS NOT NULL
        GROUP BY subject
        HAVING accuracy < 60
        ORDER BY accuracy ASC
        LIMIT 5
    ");
    
    $stmt->execute([$profile_id]);
    $weak_areas = [];
    
    while ($row = $stmt->fetch()) {
        $weak_areas[$row['subject']] = [
            'questions' => $row['questions'],
            'accuracy' => $row['accuracy']
        ];
    }
    
    return $weak_areas;
}
?>