'use strict';
/**
 * Locale structure filter
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2016 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
define(
    [
        'oro/translator',
        'text!pim/template/export/product/edit/content/structure/locales',
        'pim/form',
        'pim/fetcher-registry',
        'jquery.select2'
    ],
    function (
        __,
        template,
        BaseForm,
        fetcherRegistry
    ) {
        return BaseForm.extend({
            className: 'control-group',
            template: _.template(template),
            configure: function () {
                this.listenTo(this.getRoot(), 'channel:update:after', this.channelUpdated.bind(this));

                return BaseForm.prototype.configure.apply(this, arguments);
            },
            render: function () {
                if (!this.configured) {
                    return this;
                }

                var defaultLocalesPromise = (new $.Deferred()).resolve();
                if (_.isEmpty(this.getLocales())) {
                    defaultLocalesPromise = this.setDefaultLocales();
                }

                $.when(
                    fetcherRegistry.getFetcher('channel').fetch(this.getFormData().structure.scope),
                    defaultLocalesPromise
                ).then(function (scope) {
                    this.$el.html(
                        this.template({
                            __: __,
                            locales: this.getLocales(),
                            availableLocales: scope.locales
                        })
                    );

                    this.$('.select2').select2().on('change', this.updateModel.bind(this));
                    this.$('[data-toggle="tooltip"]').tooltip();

                    this.renderExtensions();
                }.bind(this));

                return this;
            },
            updateModel: function (event) {
                this.setLocales($(event.target).val());
            },
            setLocales: function (codes) {
                var data = this.getFormData();
                var before = data.structure.locales;

                data.structure.locales = codes;
                this.setData(data);

                if (before !== codes) {
                    this.getRoot().trigger('locales:update:after', codes);
                }
            },
            getLocales: function () {
                var structure = this.getFormData().structure;

                if (_.isUndefined(structure)) {
                    return [];
                }

                return _.isUndefined(structure.locales) ? [] : structure.locales;
            },
            getScope: function () {
                return this.getFormData().structure.scope;
            },
            channelUpdated: function () {
                this.setDefaultLocales()
                    .then(function () {
                        this.render();
                    }.bind(this));
            },
            setDefaultLocales: function () {
                return fetcherRegistry.getFetcher('channel')
                    .fetch(this.getScope())
                    .then(function (scope) {
                        this.setLocales(scope.locales);

                        return;
                    }.bind(this));
            }
        });
    }
);