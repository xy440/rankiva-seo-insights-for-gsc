=== Rankiva – SEO & Keyword Insights for Google Search Console ===
Contributors: xy440
Tags: seo, google search console, seo insights, ctr optimization, keyword tracking
Requires at least: 6.0
Tested up to: 6.9
Stable tag: 1.1.0
Requires PHP: 7.4
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Discover SEO opportunities from Google Search Console - find keywords, track rankings, and boost CTR for posts Google already ranks.

== Description ==

**Rankiva SEO & Keyword Insights for Google Search Console** is a Search Console SEO plugin that helps you grow organic traffic by showing you exactly which existing posts to update first, using real Google Search Console data.

Instead of guessing what to optimize, the plugin highlights pages that Google already ranks but are underperforming. These are posts with strong impressions, average positions on page 2 or low click-through rates. Small improvements to titles, content, or internal links can push them to page 1 and unlock fast traffic gains.

### Connect your Google Search Console account and instantly see:
* Pages ranking in positions 5–20 with high impressions
* Posts with clear CTR gaps where clicks are being left on the table
* Opportunity scores that prioritize updates by potential impact

This plugin does not replace your SEO plugin. It tells you what to optimize, so you can get results faster using tools like Rank Math or Yoast.

If you already have content indexed in Google, this plugin helps you turn that visibility into real traffic.

Unlike generic SEO plugins, this tool works entirely on real Google Search Console performance data and highlights posts with immediate improvement potential.

### Why This Plugin Exists
Most SEO tools focus on audits and recommendations.
Rankiva – SEO & Keyword Insights for Google Search Console focuses on **execution priority**.

### SEO Insights Powered by Google Search Console
Rankiva uses real Google Search Console performance data to uncover SEO opportunities hidden in your existing content. By analyzing impressions, rankings, and click-through rates, it shows exactly where small updates can deliver fast SEO wins.

### 🎯 Key Features

* **Smart SEO Opportunity Detection (powered by Google Search Console)** - Automatically identifies posts with SEO improvement potential
* **Money Posts Filter** - Find high-value pages ranking in positions 5-20 with strong impressions
* **CTR Gap Analysis** - Discover posts with impressions but below-expected click rates
* **Position Tracking** - See exact Google rankings for all your posts
* **90-Day Performance Data** - Track impressions, clicks, CTR, and average position
* **One-Click Sync** - Quick refresh of your Google Search Console data
* **Keyword-Level Insights** - See top 5 keywords for each post with position and CTR data (NEW)
* **Priority Keyword Detection** - Smart algorithm identifies best keyword to target (NEW)
* **Re-Sync on Demand** - Refresh data with one click (rate-limited to protect API quota)


### 💡 What Are "Money Posts"?

Money Posts are pages that:
* Rank between positions 5-20 in Google
* Already receive 100+ impressions per month
* Have proven Google likes your content
* Can quickly move to page 1 with optimization

These posts usually deliver the fastest SEO wins and traffic growth.

### 🚀 How It Works

1. **Connect** - Securely connect your Google Search Console account (read-only)
2. **Sync** - Import your last 90 days of search performance data
3. **Discover** - See posts ranked by opportunity score (0-100)
4. **Optimize** - Click "Edit" to improve posts with the highest potential
5. **Track** - Mark posts as updated and snooze them for 30 days

### 📊 What You'll See

For each post, the plugin shows:
* **Opportunity Score** - How much potential this post has (0-100)
* **Current Position** - Your average ranking in Google
* **Impressions** - How often your post appears in search results
* **Clicks** - How many people clicked through
* **CTR** - Click-through rate percentage
* **Insights** - Specific recommendations (e.g., "CTR gap: expected 5%, actual 2%")

### 🔐 Privacy & Security

* **Read-only access** - The plugin can only read data, never modify your GSC account
* **No data storage on external servers** - All data stays in your WordPress database
* **OAuth 2.0** - Industry-standard secure authentication
* **Disconnect anytime** - Full control over your connection

### 🎨 Use Cases

* **Content Creators** - Identify which posts to update first
* **SEO Professionals** - Data-driven content optimization strategy
* **Marketing Teams** - Track and improve organic search performance
* **Bloggers** - Maximize traffic from existing content

### 🔧 Technical Details

* Syncs data from Google Search Console API
* Tracks posts and pages (filterable via code)
* 90-day date range (last 3 months)
* Caches data for performance
* Works with any WordPress theme

### 🌟 Perfect For

* Sites with 10+ published posts
* Content sites wanting to improve organic traffic
* Anyone verified in Google Search Console
* Publishers looking for quick SEO wins

Rankiva is actively developed, with advanced analytics and automation features planned for future versions.

== Installation ==

### Automatic Installation

1. Go to **Plugins > Add New** in your WordPress admin
2. Search for "Rankiva – SEO & Keyword Insights for Google Search Console"
3. Click **Install Now** and then **Activate**
4. Go to **Rankiva** in your admin menu
5. Click **Connect Google Search Console**

### Manual Installation

1. Download the plugin ZIP file
2. Go to **Plugins > Add New > Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Click **Activate Plugin**
5. Go to **Rankiva** in your admin menu
6. Click **Connect Google Search Console**

### After Installation

1. **Connect Your Account**
   - Click "Connect Google Search Console"
   - Sign in with your Google account
   - Grant read-only permissions
   - You'll be redirected back to WordPress

2. **Run Your First Sync**
   - Click "Sync Now" to import data
   - Wait a few seconds to a couple of minutes, depending on site size and Google API response time
   - View your SEO opportunities!

3. **Start Optimizing**
   - Sort by opportunity score to see best opportunities
   - Click "Edit" to improve high-potential posts
   - Mark posts as updated when done
   - Re-sync weekly to track progress

== Frequently Asked Questions ==

= Is my Google account data secure? =

Yes! The plugin uses OAuth 2.0 (industry standard) and only requests read-only access to your Search Console data. We cannot modify anything in your Google account, and you can disconnect at any time.

= Do I need a Google Search Console account? =

Yes, you need:
1. A Google Search Console account
2. Your WordPress site verified in Google Search Console
3. At least a few days of search data

If you haven't set up Search Console yet, visit [Google Search Console](https://search.google.com/search-console) and verify your site first.

= What if I have multiple properties in Google Search Console? =

The plugin automatically detects which property matches your WordPress site URL. If you have multiple properties (http/https, www/non-www), it will automatically select the one with data.

= How often should I sync data? =

We recommend syncing:
- **Weekly** - For active content sites
- **Monthly** - For smaller or less active sites
- **After major updates** - When you've optimized several posts

Data is from the last 90 days, so weekly syncs give you the most current insights.

= What's a good opportunity score? =

- **70-100** - Excellent opportunities, prioritize these first
- **40-69** - Good opportunities, update when you have time  
- **0-39** - Lower priority, focus on higher scores first

The score is calculated using a weighted formula based on impressions, ranking position, and CTR benchmarks.

= Why am I seeing "No opportunities found"? =

This usually means:
1. **Your filters are too restrictive** - Try unchecking filters and clicking "Submit"
2. **No posts have traffic yet** - Your posts need impressions in Google Search Console
3. **New site** - Google needs time to index and rank your content
4. **All posts performing well** - Your site might already be well-optimized!

= What does "CTR gap" mean? =

CTR (Click-Through Rate) gap means your post gets impressions but fewer clicks than expected for its position. For example:
- Position #5 should get ~5% CTR
- If you're getting 2% CTR, there's a 3% gap
- This means improving your title/meta description could increase clicks

= Can I use my own Google OAuth credentials? =

Yes! For advanced users:
1. Go to **OAuth Settings** in the plugin menu
2. Select "Use Custom OAuth Credentials"
3. Enter your Google Cloud Project credentials
4. Add the redirect URI to your OAuth app

Most users should use the default OAuth proxy for simplicity.

= Does this work on localhost? =

The plugin will connect on localhost, but Google Search Console doesn't track local development sites. You'll need to connect a live, verified property to see data.

= How do I disconnect my account? =

Click the **Disconnect** button in the plugin. This will:
- Remove your Google connection
- Clear OAuth tokens
- Keep your synced data (in case you want to reconnect)

= Will this slow down my site? =

No! The plugin:
- Only syncs data when you click "Sync Now" (on-demand)
- Caches results for fast page loads
- Runs sync processes in the background
- Has minimal database queries

Your public site performance is not affected.

= Does this replace a full SEO plugin? =

No. This plugin complements SEO plugins like Rank Math or Yoast by telling you which posts to optimize. You still need an SEO plugin to apply changes.

= How is this different from Google Search Console? =

Google Search Console shows raw data. Rankiva analyzes that data and tells you exactly which posts to optimize first, with opportunity scores and keyword recommendations. It's like having an SEO consultant built into your WordPress dashboard.

= Can I see which keywords each post ranks for? =

Yes! Version 1.1 added keyword-level insights. For each post, you'll see the top 5 keywords driving traffic, their positions, impressions, clicks, and CTR. The plugin even highlights your "priority keyword" - the one with the best optimization potential.

= Is this plugin free? =

Yes, the core features are completely free. Connect your Google Search Console, sync your data, and start optimizing. No premium version required for the main functionality.

= Does this work with Rank Math / Yoast / AIOSEO? =

Yes! Rankiva complements any SEO plugin. It tells you WHICH posts to optimize, then you use Rank Math, Yoast, or AIOSEO to make the actual changes.

== Screenshots ==

1. Main dashboard showing SEO opportunities with scores, positions, and metrics
2. Card with detailed stats and action buttons
3. Connect screen for Google Search Console authentication
4. Sync screen after connecting with Google Search Console
5. OAuth settings for custom credentials

== External Services ==

This plugin connects to external services to provide its functionality:

### Google Search Console API

This plugin retrieves search performance data from the Google Search Console API to display SEO insights.

**What data is sent:**
- OAuth authentication tokens
- Your WordPress site URL (to match with Search Console properties)

**When:**
- During the initial connection setup
- When syncing data (manually triggered by clicking "Sync Now" or "Re-Sync")

**What data is received:**
- Search impressions, clicks, click-through rates, and average positions for your published posts
- Date range: Last 90 days of performance data

**Service provider:** Google LLC  
**Terms of Service:** https://policies.google.com/terms  
**Privacy Policy:** https://policies.google.com/privacy

### OAuth Authentication Proxy (Optional)

By default, this plugin uses an OAuth proxy service to simplify the Google authentication process. This is optional — advanced users can configure their own Google OAuth credentials in the plugin settings.

**What the service is:**
The OAuth proxy (https://auth.wpfixfree.com/oauth/) facilitates the Google OAuth authentication flow, allowing users to connect their Google Search Console account without creating their own Google Cloud project.

**What data is sent:**
- OAuth authorization codes generated by Google during the login process
- Your WordPress admin URL (used as a redirect callback)

**When:**
- Only during the initial connection to Google Search Console
- Only when using the default OAuth proxy method

**How data is handled:**
- The proxy processes the OAuth authorization token only to complete the authentication handshake
- Tokens are not stored, logged, or retained after authentication
- No Google Search Console performance data (impressions, clicks, rankings) passes through the proxy

**Service provider:** Developed and maintained by the plugin author

**Terms of Service:** https://auth.wpfixfree.com/terms  
**Privacy Policy:** https://auth.wpfixfree.com/privacy  

**Alternative:**
You can bypass the proxy entirely by configuring your own Google OAuth credentials in Settings → OAuth Settings. When using custom credentials, no data is sent to the proxy server.

== Changelog ==

= 1.1.0 - 2026-01-09 =
* New: Keyword-level insights - see top 5 keywords driving traffic to each post
* New: Priority keyword recommendation with smart algorithm (positions 5-20)
* New: Keyword quality badges (BEST, GREAT, GOOD, LOW) based on position
* New: Keywords Tracked stat card on dashboard
* New: Re-Sync button in header (rate-limited to once per 24 hours)
* New: Click any keyword to copy to clipboard
* New: Alt+K keyboard shortcut to expand/collapse all keyword sections
* Improved: Position color coding (green = top 3, blue = page 1, orange = page 2)
* Improved: CTR indicators showing performance vs expected CTR
* Improved: Sortable keyword table columns
* Fixed: JavaScript error
* Fixed: Posts per page dropdown not redirecting
* Fixed: Last sync time not updating after re-sync

= 1.0.0 =
- Initial release

== Upgrade Notice ==

= 1.1.0 =
New keyword insights! See top 5 keywords per post with priority recommendations. Run a sync after updating to fetch keyword data.

== Privacy Policy ==

This plugin connects to Google Search Console API to retrieve search performance data for your website.
If you use the default OAuth proxy, authentication requests temporarily pass through our proxy server only to complete Google OAuth. No Search Console data is stored or logged on the proxy.

**Data Collected:**
- Search impressions, clicks, CTR, and average position
- URL-level performance data for your published posts/pages
- Date range: Last 90 days

**Data Storage:**
- All data is stored locally in your WordPress database
- No data is sent to external servers (except during OAuth authentication)
- OAuth tokens are stored securely in WordPress options

**Third-Party Services:**
- Google Search Console API (for retrieving search data)
- OAuth authentication proxy (optional, for simplified setup)

**User Control:**
- You can disconnect at any time
- You can delete all data by deactivating the plugin
- Read-only access only (cannot modify Google account data)

For more information, see [Google's Privacy Policy](https://policies.google.com/privacy).

== Support ==

For support, please visit the [plugin support forum](https://wordpress.org/support/plugin/rankiva-seo-insights-for-gsc/).

== Credits ==

Developed by [theme-x](https://theme-x.org/)