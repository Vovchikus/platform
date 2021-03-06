<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowRestriction;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;

class WorkflowDefinitionConfigurationBuilder extends AbstractConfigurationBuilder
{
    /** @var WorkflowAssembler */
    protected $workflowAssembler;

    /** @var WorkflowDefinitionBuilderExtensionInterface[] */
    protected $extensions = [];

    /**
     * @param WorkflowAssembler $workflowAssembler
     */
    public function __construct(WorkflowAssembler $workflowAssembler)
    {
        $this->workflowAssembler = $workflowAssembler;
    }

    /**
     * @param array $configurationData
     * @return WorkflowDefinition[]
     */
    public function buildFromConfiguration(array $configurationData)
    {
        $workflowDefinitions = [];
        foreach ($configurationData as $workflowName => $workflowConfiguration) {
            $workflowDefinitions[] = $this->buildOneFromConfiguration($workflowName, $workflowConfiguration);
        }

        return $workflowDefinitions;
    }

    /**
     * @param string $name
     * @param array $configuration
     * @return WorkflowDefinition
     */
    public function buildOneFromConfiguration($name, array $configuration)
    {
        foreach ($this->extensions as $extension) {
            $configuration = $extension->prepare($name, $configuration);
        }

        $this->assertConfigurationOptions($configuration, ['entity']);

        $system = $this->getConfigurationOption($configuration, 'is_system', false);
        $startStepName = $this->getConfigurationOption($configuration, 'start_step', null);
        $entityAttributeName = $this->getConfigurationOption(
            $configuration,
            'entity_attribute',
            WorkflowConfiguration::DEFAULT_ENTITY_ATTRIBUTE
        );
        $stepsDisplayOrdered = $this->getConfigurationOption(
            $configuration,
            'steps_display_ordered',
            false
        );
        $activeGroups = $this->getConfigurationOption(
            $configuration,
            WorkflowConfiguration::NODE_EXCLUSIVE_ACTIVE_GROUPS,
            []
        );
        $recordGroups = $this->getConfigurationOption(
            $configuration,
            WorkflowConfiguration::NODE_EXCLUSIVE_RECORD_GROUPS,
            []
        );

        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition
            ->setName($name)
            ->setLabel($configuration['label'])
            ->setRelatedEntity($configuration['entity'])
            ->setStepsDisplayOrdered($stepsDisplayOrdered)
            ->setSystem($system)
            ->setActive($configuration['defaults']['active'])
            ->setPriority($configuration['priority'])
            ->setEntityAttributeName($entityAttributeName)
            ->setExclusiveActiveGroups($activeGroups)
            ->setExclusiveRecordGroups($recordGroups)
            ->setConfiguration($this->filterConfiguration($configuration));

        $workflow = $this->workflowAssembler->assemble($workflowDefinition, false);

        $this->setSteps($workflowDefinition, $workflow);
        $workflowDefinition->setStartStep($workflowDefinition->getStepByName($startStepName));

        $this->setEntityAcls($workflowDefinition, $workflow);
        $this->setEntityRestrictions($workflowDefinition, $workflow);

        return $workflowDefinition;
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @param Workflow $workflow
     */
    protected function setSteps(WorkflowDefinition $workflowDefinition, Workflow $workflow)
    {
        $workflowSteps = [];
        foreach ($workflow->getStepManager()->getSteps() as $step) {
            $workflowStep = new WorkflowStep();
            $workflowStep
                ->setName($step->getName())
                ->setLabel($step->getLabel())
                ->setStepOrder($step->getOrder())
                ->setFinal($step->isFinal());

            $workflowSteps[] = $workflowStep;
        }

        $workflowDefinition->setSteps($workflowSteps);
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @param Workflow $workflow
     */
    protected function setEntityAcls(WorkflowDefinition $workflowDefinition, Workflow $workflow)
    {
        $entityAcls = [];
        foreach ($workflow->getAttributeManager()->getEntityAttributes() as $attribute) {
            foreach ($workflow->getStepManager()->getSteps() as $step) {
                $updatable = $attribute->isEntityUpdateAllowed()
                    && $step->isEntityUpdateAllowed($attribute->getName());
                $deletable = $attribute->isEntityDeleteAllowed()
                    && $step->isEntityDeleteAllowed($attribute->getName());

                if (!$updatable || !$deletable) {
                    $entityAcl = new WorkflowEntityAcl();
                    $entityAcl
                        ->setAttribute($attribute->getName())
                        ->setStep($workflowDefinition->getStepByName($step->getName()))
                        ->setEntityClass($attribute->getOption('class'))
                        ->setUpdatable($updatable)
                        ->setDeletable($deletable);
                    $entityAcls[] = $entityAcl;
                }
            }
        }

        $workflowDefinition->setEntityAcls($entityAcls);
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @param Workflow           $workflow
     */
    protected function setEntityRestrictions(WorkflowDefinition $workflowDefinition, Workflow $workflow)
    {
        $restrictions = $workflow->getRestrictions();
        $workflowRestrictions = [];
        foreach ($restrictions as $restriction) {
            $workflowRestriction = new WorkflowRestriction();
            $workflowRestriction
                ->setField($restriction->getField())
                ->setAttribute($restriction->getAttribute())
                ->setEntityClass($restriction->getEntity())
                ->setMode($restriction->getMode())
                ->setValues($restriction->getValues())
                ->setStep($workflowDefinition->getStepByName($restriction->getStep()));

            $workflowRestrictions[] = $workflowRestriction;
        }
        $workflowDefinition->setRestrictions($workflowRestrictions);
    }

    /**
     * @param array $configuration
     * @return array
     */
    protected function filterConfiguration(array $configuration)
    {
        $configurationKeys = [
            WorkflowConfiguration::NODE_STEPS,
            WorkflowConfiguration::NODE_ATTRIBUTES,
            WorkflowConfiguration::NODE_TRANSITIONS,
            WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS,
            WorkflowConfiguration::NODE_ENTITY_RESTRICTIONS
        ];

        return array_intersect_key($configuration, array_flip($configurationKeys));
    }

    /**
     * @param WorkflowDefinitionBuilderExtensionInterface $extension
     */
    public function addExtension(WorkflowDefinitionBuilderExtensionInterface $extension)
    {
        $this->extensions[] = $extension;
    }
}
