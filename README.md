# Dixeo AI Plugin for Moodle

Core plugin that handles all API communication with the Dixeo AI API for Moodle 4.5+.

## Features

- **API Client**: HTTP client for Dixeo API communication with proper error handling
- **Job Polling**: Async job status polling with configurable timing per job type
- **Context Service**: Gathers course, section, and module context for AI processing
- **Credit Management**: Balance checking, transaction history, and usage reports
- **Admin Settings**: Configure API URL, key, and namespace

## Requirements

- Moodle 4.5 or higher
- PHP 8.1 or higher
- Dixeo API account and API key

## Installation

1. Copy the `dixeo` folder to `/local/dixeo/`
2. Visit Site Administration > Notifications to complete installation
3. Configure the plugin at Site Administration > Plugins > Local plugins > Dixeo AI

## Configuration

### API Settings

- **API URL**: The Dixeo API endpoint (default: `https://api.dixeo.com`)
- **API Key**: Your Dixeo API key (required)
- **Namespace**: Unique identifier for this Moodle site (auto-generated from site identifier)

## Usage

### Services

The plugin provides several services for use by other plugins:

```php
// High-level AI operations
$service = new \local_dixeo\service\dixeo_service();
$result = $service->generate_module('page', 'Create content about...', $context);

if ($result->is_completed()) {
    $content = $result->get_content();
    $creditsused = $result->creditsused;
} else {
    // Handle pending job
    $jobid = $result->jobid;
}

// Credit operations
$creditservice = new \local_dixeo\service\credit_service();
$balance = $creditservice->get_balance();
echo $balance->get_formatted_balance(); // "$1.50"

// Context gathering
$contextservice = new \local_dixeo\service\context_service();
$context = $contextservice->get_course_context_markdown($courseid);
```

### Exception Handling

```php
use local_dixeo\api\exception\authentication_exception;
use local_dixeo\api\exception\payment_required_exception;
use local_dixeo\api\exception\rate_limit_exception;

try {
    $result = $service->generate_module(...);
} catch (authentication_exception $e) {
    // Invalid API key
} catch (payment_required_exception $e) {
    // Insufficient credits
    $balance = $e->get_current_balance();
} catch (rate_limit_exception $e) {
    // Too many requests
    $retryafter = $e->get_retry_after();
}
```

## Capabilities

| Capability | Description | Default Roles | Context |
|------------|-------------|---------------|---------|
| `local/dixeo:manage` | Manage settings and view admin reports | Manager | System |
| `local/dixeo:generate` | Generate new modules (page, label, quiz, glossary) | Editing Teacher, Manager | Course |
| `local/dixeo:edit` | Edit existing modules using AI | Editing Teacher, Manager | Module |
| `local/dixeo:viewusage` | View credit usage reports | Manager | System |

## API Reference

### Dixeo API Endpoints Used

- `POST /v1/modules/generate` - Generate new module content
- `POST /v1/modules/regenerate` - Edit existing module content
- `GET /v1/jobs/{id}` - Get job status
- `GET /v1/credits/balance` - Get current credit balance
- `GET /v1/credits/transactions` - Get transaction history
- `GET /v1/credits/usage/stats` - Get usage statistics

### Polling Configuration

Different job types have different timing:

| Job Type | Initial Delay | Poll Interval | Timeout |
|----------|--------------|---------------|---------|
| fill_module | 2s | 2s | 2min |
| edit_module | 2s | 2s | 2min |
| generate_module | 3s | 2s | 60s |
| course_gen | 5s | 3s | 5min |

## License

GNU GPL v3 or later

## Support

For support, please contact Dixeo at support@dixeo.io
