Role Based Theme Switcher
===================

Role based Theme Switcher module help users to set
different themes for different Roles.

Configuration:
--------------
Role based Theme Switcher module have configuration setting page 

Configuration page path: /admin/structure/role_based_theme_switcher/settings

Use of Weight Field:
--------------------
Use of weight in the form is to remove the complexity of multiple roles.
To give higher priority to any role drag the role to the bottom for ex:
If a user has multiple roles like: authenticated, editor etc. Now to remove
complexity of which theme should be applied to user, drag the role to the bottom
and the particular theme should be applied.

If you move authenticated role to the bottom i.e after editor role then, theme
assigned to authenticated role will be applied for the user.
