<?php
/**
 * @file
 * Contains \Drupal\rsvplist\Form\RSVPForm
 */
 namespace Drupal\rsvplist\Form;

 use Drupal\Core\Database\Database;
 use Drupal\Core\Form\FormBase;
 use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an RSVP Email form.
*/
class RSVPForm extends FormBase {
    /**
     * (@inheritdoc)
     * This is where we set an id for the form
     */
    public function getFormId() {
        return 'rsvplist_email_form';
    }

    /**
     * (@inheritdoc)
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        // $node is built out of the url
        $node = \Drupal::routeMatch()->getParameter('node');
        // $nid is assigned with the node id value from $node
        $nid = $node->nid->value;

        // All values set with $form will represent the fields that we want to create.
        // The t() is used to return a translatable string instead of a regular string value,
        // it will allow strings to be translated using translate features.
        $form['email'] = array(
            '#title' => t('Email Address'),
            '#type' => 'textfield',
            '#size' => 25,
            '#description' => t("We'll send updates to the email address you provide"),
            '#required' => TRUE
        );
        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('RSVP')
        );
        $form['nid'] = array(
            '#type' => 'hidden',
            '#value' => $nid
        );
        
        return $form;
    }

    /**
     * (@inheritdoc)
     * 
     * The method name validateForm() is expected by Drupal 
     * when creating a form validation method
     * 
     * A drupal class method called service() is used to handle email validation easily,
     * it returns a boolean and is then used in a condition to return error messages.
     * 
     * For some reason %mail does not display with the given value 
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        $value = $form_state->getValue('email');
        $isValidEmail = \Drupal::service('email.validator')->isValid($value);
        if (!$isValidEmail) {
            $form_state->setErrorByName(
                'email', 
                t('The email address %mail is not valid.'),
                array('%mail' => $value)
            );
            return;
        }

        // Check if the email submitted has already rsvp-ed for this node/content
        $node = \Drupal::routeMatch()->getParameter('node');
        $select->fields('r', array('nid'));
        $select->condition('nid', $node->id());
        $select->condition('mail', $value);
        $results = $select->execute();
        
        // If existing email is found with the current nid, throw error
        if (!empty($results->fetchCol())) {
            $form_state->setErrorByName('email', t('The address %mail is already subscribed to this list.', array('%mail' => $value)));
        }
    }

    /**
     * (@inheritdoc)
     * 
     * The "&" used in &$form param means we set that param to be a reference of the original variable passed to the function
     * Which means if &$form is changed, the original variable will also change
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        // This gets the id of the user who is currently logged in 
        $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());

        db_insert('rsvplist')
        ->fields(array(
            'mail' => $form_state->getValue('email'),
            'nid' => $form_state->getValue('nid'),
            'uid' => $user->id(),
            'created' => time()
        ))
        ->execute();

        drupal_set_message(t('Thank you for your RSVP, you are on the list for the event'));
    }
}