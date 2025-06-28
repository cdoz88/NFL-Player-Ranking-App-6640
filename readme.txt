=== NFL Player Ranking ===
Contributors: yourname
Tags: nfl, football, rankings, sports, consensus
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 2.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create consensus rankings for NFL players by position with drag-and-drop functionality and tier system.

== Description ==

NFL Player Ranking allows you to create interactive NFL player rankings with the following features:

* **Admin Panel**: Upload CSV files of NFL players by position and week
* **User Rankings**: Drag-and-drop interface for creating custom rankings with tier system
* **Consensus Rankings**: Automatic calculation of consensus rankings from all user submissions
* **Multi-Week Support**: Support for different NFL weeks (Week 1-18, Offseason, Rookies)
* **Position-Based**: Separate rankings for QB, RB, WR, and TE positions
* **Responsive Design**: Works on all devices with modern styling

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/nfl-player-ranking` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the NFL Rankings menu in your WordPress admin to configure the plugin.

== Usage ==

1. **Upload Players**: Go to NFL Rankings > Manage Players and upload CSV files with player data (name, team, opponent columns required).
2. **Set Current Week**: Go to NFL Rankings > Settings and set the current week.
3. **Add Shortcode**: Add `[nfl_player_ranking]` to any page or post where you want the ranking interface to appear.
4. **Users Rank**: Logged-in users can drag and drop players into tiers to create their rankings.
5. **View Consensus**: The consensus rankings are automatically calculated from all user submissions.

== Frequently Asked Questions ==

= What CSV format is required? =

Your CSV file must contain at least three columns with headers: name, team, and opponent. The headers are case-insensitive.

Example:
```
name,team,opponent
Josh Allen,Buffalo Bills,Miami Dolphins
Cooper Kupp,Los Angeles Rams,Arizona Cardinals
```

= How are consensus rankings calculated? =

Consensus rankings are calculated by averaging the positions of each player across all user rankings. Players are then sorted by their average ranking position.

= Can I customize the tier names? =

Currently, the plugin uses standard tier names (Tier 1, Tier 2, etc.). Custom tier names may be added in future versions.

= Do users need to be logged in to create rankings? =

Yes, users must be logged in to create and save their rankings. However, anyone can view the consensus rankings.

== Screenshots ==

1. Admin panel for uploading player data
2. User ranking interface with drag-and-drop functionality
3. Consensus rankings display
4. Mobile-responsive design

== Changelog ==

= 2.0.1 =
* Updated CSS styling with improved padding and transparency
* Fixed responsive design issues
* Improved drag-and-drop interface
* Enhanced tier system functionality
* Better error handling for CSV uploads

= 2.0.0 =
* Major redesign with modern dark theme
* Added tier system for player rankings
* Improved drag-and-drop interface
* Better mobile responsiveness
* Enhanced consensus calculations

= 1.0.0 =
* Initial release
* Basic player ranking functionality
* CSV upload support
* Consensus ranking calculations

== Upgrade Notice ==

= 2.0.1 =
This version includes important CSS fixes and improvements to the user interface. Update recommended for better user experience.