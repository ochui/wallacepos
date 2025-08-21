/**
 * notifications.js is part of Wallace Point of Sale system (WPOS)
 *
 * notifications.js provides centralized notification management using iGrowl
 * to replace legacy browser alert() calls.
 *
 * WallacePOS is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3.0 of the License, or (at your option) any later version.
 *
 * WallacePOS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details:
 * <https://www.gnu.org/licenses/lgpl.html>
 *
 * @package    wpos
 * @copyright  Copyright (c) 2014 WallaceIT. (https://wallaceit.com.au)
 * @since      File created for alert migration
 */

function WPOSNotifications() {
  // Global iGrowl configuration as specified in requirements
  this.defaultConfig = {
    delay: 2500, // Auto-dismiss after 2.5 seconds
    small: false, // Regular size notifications
    spacing: 30, // 30px spacing between alerts
    placement: {
      x: "right", // Horizontal placement: right
      y: "top", // Vertical placement: top
    },
    offset: {
      x: 20, // 20px from right edge
      y: 20, // 20px from top edge
    },
  };

  /**
   * Show a notification using iGrowl with consistent styling
   * @param {string} message - The message to display
   * @param {string} type - Type of notification: 'info', 'success', 'warning', 'error'
   * @param {string} title - Optional title for the notification
   * @param {object} options - Optional override settings
   */
  this.show = function (message, type, title, options) {
    type = type || "info";

    var config = $.extend(
      true,
      {},
      this.defaultConfig,
      {
        type: type,
        message: message,
        title: title || null,
      },
      options || {}
    );

    // Show the notification
    $.iGrowl(config);
  };

  /**
   * Show an info notification (default blue styling)
   * @param {string} message - The message to display
   * @param {string} title - Optional title
   * @param {object} options - Optional additional settings
   */
  this.info = function (message, title, options) {
    this.show(message, "info", title, options);
  };

  /**
   * Show a success notification (green styling)
   * @param {string} message - The message to display
   * @param {string} title - Optional title
   * @param {object} options - Optional additional settings
   */
  this.success = function (message, title, options) {
    this.show(message, "success", title, options);
  };

  /**
   * Show a warning notification (yellow/orange styling)
   * @param {string} message - The message to display
   * @param {string} title - Optional title
   * @param {object} options - Optional additional settings
   */
  this.warning = function (message, title, options) {
    this.show(message, "notice", title, options);
  };

  /**
   * Show a notice notification (same as warning)
   * @param {string} message - The message to display
   * @param {string} title - Optional title
   * @param {object} options - Optional additional settings
   */
  this.notice = function (message, title, options) {
    this.show(message, "notice", title, options);
  };

  /**
   * Show an error notification (red styling)
   * @param {string} message - The message to display
   * @param {string} title - Optional title
   * @param {object} options - Optional additional settings
   */
  this.error = function (message, title, options) {
    this.show(message, "error", title, options);
  };

  /**
   * Direct replacement for alert() calls
   * This function provides backward compatibility while migrating
   * @param {string} message - The alert message
   * @param {object} options - Optional additional settings
   */
  this.alert = function (message, options) {
    // Determine notification type based on message content
    var type = "info"; //Type of alert, available options are: info, success, notice, error, simple
    var title = null;

    if (
      message.toLowerCase().includes("error") ||
      message.toLowerCase().includes("failed") ||
      message.toLowerCase().includes("could not") ||
      message.toLowerCase().includes("cannot") ||
      message.toLowerCase().includes("access denied")
    ) {
      type = "error";
      title = "Error";
    } else if (message.toLowerCase().includes("warning") || message.toLowerCase().includes("please") || message.toLowerCase().includes("must")) {
      type = "notice";
      title = "Warning";
    } else if (message.toLowerCase().includes("success") || message.toLowerCase().includes("completed") || message.toLowerCase().includes("updated")) {
      type = "success";
      title = "Success";
    }

    this.show(message, type, title, options);
  };

  /**
   * Dismiss all notifications
   * @param {string} placement - Optional placement to dismiss, or 'all' for all notifications
   */
  this.dismissAll = function (placement) {
    if (typeof $.iGrowl.prototype.dismissAll === "function") {
      $.iGrowl.prototype.dismissAll(placement || "all");
    }
  };
}
