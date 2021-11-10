Building Forms with ACF Extended
================================

CiviCRM Profile Sync enables Forms to be built for the front-end of your website with the UI provided by the [ACF Extended](https://wordpress.org/plugins/acf-extended/) (ACFE) plugin. These Forms can send their data directly to CiviCRM in a similar (though currently more limited) way to the Caldera Forms CiviCRM plugin.

Please be aware that Form building with ACFE is at an early stage of development and is currently limited to submitting data for Contacts, Activities and Cases. This does, however, provide enough functionality to build some fairly powerful and useful Forms.

*Note:* ACFE requires the [Advanced Custom Fields Pro](https://www.advancedcustomfields.com/pro/) plugin.

## Features

* Add unlimited Contacts on the same Form
* Auto-populate Form if the Contact is logged in
* Checksum support to auto-populate Form with URLs like `example.org/some-page/?cid={contact.contact_id}&{contact.checksum}`
* Define Contact Type: Organization, Individual, Household, and Custom Contact Subtypes
* Map data from Custom Fields
* Add Relationships to each Contact
* Add Free Memberships (CiviMember Component must be active)
* Create Activities on Form submission
* Open a Case on Form submission (CiviCase Component must be active)

## Getting Started

There are three steps to create a front-end Form using ACF Extended and CiviCRM:

1. Create an ACF Field Group that contains all your Form elements
2. Create an ACFE Form and add the Actions that handle submissions
3. Embed the Form in a WordPress page on your site

The best place to learn about these steps is at the [ACF Extended website](https://www.acf-extended.com/features/modules/dynamic-forms). What follows here will explain the additional steps and setup needed to connect your Form to CiviCRM.

## The ACF Field Group

When you have added all your Form elements to your ACF Field Group, you are going to need to let CiviCRM Profile Sync know that your Field Group is connected to CiviCRM. To do so, you are going to use the "CiviCRM Entity in ACFE" Location Rule.

For each kind of CiviCRM Entity that the Form references, you should add the corresponding Location Rule. So if your Form is going to reference two Contacts (e.g. one of Sub-type "Parent" and one of Sub-type "Child") and create an Activity of type "Feedback", then your list of Location Rules should be:

* CiviCRM Entity in ACFE - is - Parent
* CiviCRM Entity in ACFE - is - Child
* CiviCRM Entity in ACFE - is - Feedback

This will allow CiviCRM Profile Sync to help you map the Fields in the Field Group to their corresponding Fields on the relevant CiviCRM Entities. Save your Field Group.

*Tip:* Set the ACF Field Group “Active” setting to “No” to prevent the Field Group from being visible anywhere except in your Form.

## The ACF Fields

Each of the Fields in your Field Group can now be connected to CiviCRM. Make sure you have used the correct ACF Field Types as detailed in the [ACF Integration Documentation](/docs/ACF.md). When you look at the settings for each Field, you should see an option that allows you to specify the Field on the CiviCRM Entity that the ACF Field should map to. If you don't see the proper mapping, then it could be that you have chosen the wrong ACF Field Type. Map as many Fields as you can in the Field Group - especially those which need “choices” from CiviCRM.

### Custom ACF Fields

The CiviCRM Profile Sync plugin also provides a number of custom ACF Fields for use in front-end ACFE Forms which you will see as choices in the "Field Type" dropdown when you add a new ACF Field to a Field Group. These are grouped under "CiviCRM ACFE Forms" and are:

#### CiviCRM Country

This Field auto-populates with the Countries in CiviCRM and returns the numeric ID of the CiviCRM Country for submission to CiviCRM. To use the Field in the Form, map it to "Country ID" in the "Address Actions" section of the CiviCRM Contact Action.

#### CiviCRM State

This Field auto-populates with the States/Provinces in CiviCRM and returns the numeric ID of the CiviCRM State/Province for submission to CiviCRM. To filter the list of States/Provinces by Country on the front-end, you will need to link to the CiviCRM Country Field, which must be in the same ACF Field Group. To use the Field in the Form, map it to "State/Province ID" in the "Address Actions" section of the CiviCRM Contact Action.

#### CiviCRM County

This Field auto-populates with the Counties in CiviCRM and returns the numeric ID of the CiviCRM County for submission to CiviCRM. To filter the list of Counties by State/Province on the front-end, you will need to link to the CiviCRM State Field, which must be in the same ACF Field Group. To use the Field in the Form, map it to "County ID" in the "Address Actions" section of the CiviCRM Contact Action.

## The ACF Extended Form

The ACF Extended Form that you have created can now make use of the CiviCRM Actions (equivalent to "processors" in Caldera Forms CiviCRM) to send data to CiviCRM. The available Actions are:

* CiviCRM Contact Action
* CiviCRM Activity Action
* CiviCRM Case Action (if the CiviCase Component is active in CiviCRM)
* CiviCRM Participant Action (if the CiviEvent Component is active in CiviCRM)
* CiviCRM Email Action (if the Email API Extension is active in CiviCRM)

For the Parent + Child + Feedback example above, you will need to add two CiviCRM Contact Actions and a CiviCRM Activity Action and name them appropriately in their "Action name" Fields.

The first of the CiviCRM Contact Actions (the Parent) should be set as the Action for the Contact who is submitting the Form. Contact Type should be set to "Individual" and Contact Sub Type should be set to "Parent". Choose a Dedupe Rule and check the Contact Entities that are required. Contact Fields and Custom Fields can now be mapped in the "Mapping" tab.

The second CiviCRM Contact Action (the Child) should be filled out similarly. The one important difference is that it should be set such that "Submitter" is off - this will mean that the Relationship to the Submitter can be defined in the "Relationships" tab of the Action. For this example, set a Relationship to "Child Of".

*Tip:* Caldera Forms CiviCRM had separate "processors" for Email, Website, Relationship, Group etc. There is no need for separate ACFE Form Actions for these mappings because all of them are contained in the CiviCRM Contact Action.

Lastly, configure the CiviCRM Activity Action to specify the Activity Type and Status (and, if active, the CiviCampaign). You will then need to assign the Mappings for this Action. The big difference to the CiviCRM Contact Action is that the "Contact References" can be assigned via one of three sources:

* Another Action in the Form
* A Contact in the CiviCRM database
* A Field in the Form

You will notice that choosing one of these sources causes the others to disappear. Activity Fields and Custom Fields can be mapped in the same way as the CiviCRM Contact Action.

*Tip:* Set the ACFE Form “Field groups locations rules” setting to “No”.

Should you want to open a CiviCRM Case in the Form, you'll need to have added the relevant ACF Fields to the Field Group and added the Location Rule specifying the Case Type. You can then use the CiviCRM Case Action (with similar configuration options to the Actions detailed above) to open a Case.

*Tip:* If you have used Caldera Forms CiviCRM, then the available options in each of the Actions should be relatively familiar to you. I have tried to keep the functionality in this UI as recognisable as possible.

## What's Missing?

When compared to Caldera Forms CiviCRM, the following features are not available yet:

* CiviDiscount integration for Participant Registration and special field (requires CiviDiscount Extension)
* Add Non-free Memberships (CiviMember Component must be active)
* Add Non-free Participants (CiviEvent Component must be active)
* Add Contributions with Line Items (for live transactions)

As you can see, most of these are related to payment transactions. The plan is for this plugin to work with [ACF Extended Pro](https://www.acf-extended.com/pro) for payments via Stripe. For more comprehensive payment integration, please look at the [Integrate CiviCRM with WooCommerce](https://github.com/WPCV/wpcv-woo-civi-integration) plugin.

## Credits

Many thanks to:

* [Tadpole Collective](https://tadpole.cc/) for funding the integration of CiviCRM with the ACF Extended Forms UI.
