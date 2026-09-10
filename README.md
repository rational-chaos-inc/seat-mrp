# SeAT Member Rewards Programme

A SeAT plugin for tracking corporation member activity across mining, PvP combat, and tax wallet contributions.

## Features

- Track member activities: mining, PvP kills/losses, tax wallet bounties
- Aggregate data per user (main character + linked alts)
- View totals and averages across linked characters
- Configurable time windows (daily, weekly, monthly, quarterly, yearly)
- Member dashboard for personal activity tracking
- Director dashboard with corporation-wide summaries and league tables
- Member drill-down and search/filter capabilities
- Activity alerts and target setting
- Hybrid Manager-Core integration (optional)

## Installation

```bash
composer require rci/member-rewards
php artisan migrate
php artisan vendor:publish --tag=member-rewards-config
```

## Configuration

Edit `config/member-rewards.php` to configure:
- Polling intervals for data collection
- Cache TTL for aggregations
- ESI retry settings
- Supported time windows
- Manager-Core integration

## Architecture

### Phase 1: Foundation (✓ Complete)
- [x] Plugin bootstrap and service provider
- [x] Database schema (activities, alerts, aggregation cache)
- [x] Eloquent models with aggregation scopes
- [x] Core service layer structure (ESI, tax wallet, mining, orchestration)
- [x] Configuration and route placeholders
- [x] Character linking via SeAT's built-in relationships

### Phase 2: Data Collection (In Progress)
- [ ] ESI API integration for kills/losses
- [ ] Tax wallet data collection
- [ ] Mining data integration (via Mining-Manager)
- [ ] Activity collection orchestration job

### Phase 3: Aggregation & Performance (✓ Complete)
- [x] On-the-fly aggregation logic with comprehensive metrics
- [x] Optional caching layer (configurable TTL)
- [x] Time window calculations (day/week/month/quarter/year)
- [x] Character grouping logic with per-character breakdowns
- [x] League table generation and ranking
- [x] Activity trend analysis over time
- [x] Cache management commands

### Phase 4: Access Control & Views (✓ Complete)
- [x] Permission model implementation (view_own_activities, view_all_activities)
- [x] Member dashboard with time windows and activity breakdown
- [x] Director dashboard with corp-wide summaries
- [x] League tables and member rankings
- [x] Character detail drilldown (member view)
- [x] Member detail drilldown (director view)
- [x] Permission middleware and route protection

### Phase 5: API & Advanced Features (✓ Complete)
- [x] REST API endpoints (activities, aggregations, league tables)
- [x] Alert system (threshold, unusual activity, member alerts)
- [x] Notification dispatch (in-app, email, webhook stubs)
- [x] Alert management (CRUD via API)
- [x] Test notification endpoint
- [x] Scheduled alert checking

### Phase 6: Manager-Core Integration (✓ Complete)
- [x] EventBus subscription for character/mining/ESI events
- [x] ESI FastPoll integration for real-time kills/losses
- [x] Event listeners for Mining-Manager integration
- [x] Event publishing (activities, alerts)
- [x] Cross-plugin communication via PluginBridge
- [x] Graceful fallback when Manager-Core unavailable

## Development Notes

- Uses Laravel PSR-4 autoloading
- Database migrations auto-discovered by SeAT
- Permissions managed via Spatie/Laravel-Permission
- Graceful fallback when Manager-Core is not available

## License

MIT
