(function ($, Drupal) {
  Drupal.behaviors.eccParentsNodeForm = {
    attach: function (context, settings) {
      $(once("ecc-parents", "#edit-submit", context)).on('click', (e) => {
        let state = $("#edit-moderation-state-0-state").val();
        if (state !== 'published') {
          if (false == confirm(Drupal.t("If you save the node as this, its children may end up unpublished. " +
            "Do you want to continue?"))) {
            e.preventDefault();
          };
        }
      });
    }
};
})(jQuery, Drupal);
