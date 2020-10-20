<?php
/**
 * Tests the Fieldmanager Content Field.
 *
 * @group field
 * @group content
 */
class Test_Fieldmanager_Content_Field extends WP_UnitTestCase {

	protected $plaintext = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';

	protected $html = '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec odio. Praesent libero. Sed cursus ante dapibus diam. Sed nisi. Nulla quis sem at nibh elementum imperdiet. Duis sagittis ipsum. Praesent mauris. Fusce nec tellus sed augue semper porta. Mauris massa. Vestibulum lacinia arcu eget nulla. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Curabitur sodales ligula in libero. Sed dignissim lacinia nunc. </p>
<p>Curabitur tortor. Pellentesque nibh. Aenean quam. In scelerisque sem at dolor. Maecenas mattis. Sed convallis tristique sem. <b>Vestibulum lacinia arcu eget nulla</b>. Proin ut ligula vel nunc egestas porttitor. Morbi lectus risus, iaculis vel, suscipit quis, luctus non, massa. Fusce ac turpis quis ligula lacinia aliquet. Mauris ipsum. Nulla metus metus, ullamcorper vel, tincidunt sed, euismod in, nibh. Quisque volutpat condimentum velit. </p>';

	protected $markdown = '# Regna disposuit

## Procul feror

Lorem markdownum felle instabat; certe nonne, simul Amphimedon at rure
admonuisse deorum, nunc pectora vero per. Vestras dictaque rerum genetrix vivit,
est qui ora doceri memor, tum. Sine pontus concussit, annos, Credulitas tabellas
dictis semina sumit in artus concipit prohibente mendacique ense, tenentibus
inimicos. Aufer constitit dedisse severa artus quoque
[pater](http://fuitminimae.org/tenuissimus) posita, cicatrix trux quidem ipsa
visa?

Esse *Cumaea* suae! Petit tum lacerum *caelum*, ferre? Vera dona Cnosiacaeque
arces! Est et laetus exercet turgida Phineus sunt; aeolis suis quoque, sub! Iam
lumina sanguisque aera superstitibus Hectoris et
[duorum](http://nunc.com/parentiargolicae) limitibus curvavit.

## Texere coegit et iubet imagine dextra alto

Idque ostendere lacerum alas, rostro sed visus auras irascere. Est et ardet et
contraque populi. In aquis dederat credulitate more scopulis nescio coniuge
squalentia putri cupiens lenisque et ferarum cursu, ab futuri heros.

> Sed meliora e tamen passim confessaque errore quinos sternit. Est vel in bis
> quoque litora corneaque alasque cetera quidque ingeniis. Et meae, et facis
> reticere, plebe, vestigia!

## Hunc licuit Coroniden adimit salientia cuspidis capacem

Terram indetonsusque notatas est coagula dixit. Inclusos enim, constiterat
prolem: subit deum sonantibus nomina declinat tibi sanguine custodia.

var click = touchscreen.pretest_retina_rpc.refresh_pci(51, cable, dashboard(ad_tftp_thyristor(software_bar, adfDll), clonePetabyte));
biometrics.lock_thyristor_enterprise += mail_remote(ssd);
var docking = languagePostscriptWindow(hsf(blu(quad_hard, 4), dpiBurnMemory), memoryKindle, -1);
var dimm = powerBurnSuperscalar;
bccLog.software_plain_ftp(boot_frozen_word, ethernet, half(clipThird, 2, 95));

## Rogabam remotis quam

Est novae sed illa manus in arvaque sed sonuere magno, dum **in sui** obstantes
suis, mea. Disce *probat* aevo **terrigenae ramis**, inbellibus viscera, saeva,
dat mora. Colla imitataque vult. Observo his et non fluminis vixque, proceres
truncoque genibus Persephones uteri primosque tenuere, [inque
unguibus](http://www.capeet.com/aura) mediis: undis.

Phlegraeis tum agat, auctor sono subiectis prendere et huic dei pressant. Pars
**et nam**.

Aptarique damnandus frustra ardentior moderato habeo mater miscentem cumulum,
ait in orientis coniugis sitim fortunaeque alendi de longus et. Nec indulgere
elementa plausis? Agit guttur genitor. Aeneaeque illam: est periit aliis et
debuit superis; ubi ramos non pollice.';

	public function setUp() {
		parent::setUp();
		Fieldmanager_Field::$debug = true;
	}

	public function test_plaintext() {
		$plaintext = new Fieldmanager_Content(
			[
				'name'    => 'plaintext_content',
				'content' => $this->plaintext,
			]
		);

		$html = new Fieldmanager_Content(
			[
				'name'    => 'html_content',
				'content' => $this->html,
			]
		);

		$markdown = new Fieldmanager_Content(
			[
				'name'    => 'markdown_content',
				'content' => $this->markdown,
			]
		);

		$this->assertEquals( $plaintext->form_element(), esc_html( $this->plaintext ), 'Failed escaping plaintext to plaintext.' );
		$this->assertEquals( $html->form_element(), esc_html( $this->html ), 'Failed escaping html to plaintext.' );
		$this->assertEquals( $markdown->form_element(), esc_html( $this->markdown ), 'Failed escaping markdown to plaintext.' );
	}

	public function test_html() {
		$plaintext = new Fieldmanager_Content(
			[
				'name'         => 'plaintext_content',
				'content'      => $this->plaintext,
				'content_type' => 'html',
			]
		);

		$html = new Fieldmanager_Content(
			[
				'name'         => 'html_content',
				'content'      => $this->html,
				'content_type' => 'html',
			]
		);

		$markdown = new Fieldmanager_Content(
			[
				'name'         => 'markdown_content',
				'content'      => $this->markdown,
				'content_type' => 'html',
			]
		);

		$this->assertEquals( $plaintext->form_element(), wp_kses_post( $this->plaintext ), 'Failed escaping plaintext to html.' );
		$this->assertEquals( $html->form_element(), wp_kses_post( $this->html ), 'Failed escaping html to html.' );
		$this->assertEquals( $markdown->form_element(), wp_kses_post( $this->markdown ), 'Failed escaping markdown to html.' );
	}

	public function test_markdown() {
		$plaintext = new Fieldmanager_Content(
			[
				'name'         => 'plaintext_content',
				'content'      => $this->plaintext,
				'content_type' => 'markdown',
			]
		);

		$html = new Fieldmanager_Content(
			[
				'name'         => 'html_content',
				'content'      => $this->html,
				'content_type' => 'markdown',
			]
		);

		$markdown = new Fieldmanager_Content(
			[
				'name'         => 'markdown_content',
				'content'      => $this->markdown,
				'content_type' => 'markdown',
			]
		);

		$this->assertEquals( $plaintext->form_element(), ( new Parsedown() )->text( $this->plaintext ), 'Failed escaping plaintext to markdown.' );
		$this->assertEquals( $html->form_element(), ( new Parsedown() )->text( $this->html ), 'Failed escaping html to markdown.' );
		$this->assertEquals( $markdown->form_element(), ( new Parsedown() )->text( $this->markdown ), 'Failed escaping markdown to markdown.' );
	}
}
