<?php

namespace App\Entity;

use App\Repository\InvoiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=InvoiceRepository::class)
 */
class Invoice
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $invoice_number;

    /**
     * @ORM\ManyToOne(targetEntity=ShipmentAddress::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $delivery_address;

    /**
     * @ORM\ManyToMany(targetEntity=InvoiceProduct::class)
     */
    private $products;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $invoice_date;

    /**
     * @ORM\Column(type="date_immutable")
     */
    private $delivery_date;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $status;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoice_number;
    }

    public function setInvoiceNumber(string $invoice_number): self
    {
        $this->invoice_number = $invoice_number;

        return $this;
    }

    public function getDeliveryAddress(): ?ShipmentAddress
    {
        return $this->delivery_address;
    }

    public function setDeliveryAddress(?ShipmentAddress $delivery_address): self
    {
        $this->delivery_address = $delivery_address;

        return $this;
    }

    /**
     * @return Collection<int, InvoiceProduct>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(InvoiceProduct $product): self
    {
        if (!$this->products->contains($product)) {
            $this->products[] = $product;
        }

        return $this;
    }

    public function removeProduct(InvoiceProduct $product): self
    {
        $this->products->removeElement($product);

        return $this;
    }

    public function getInvoiceDate(): ?\DateTimeImmutable
    {
        return $this->invoice_date;
    }

    public function setInvoiceDate(\DateTimeImmutable $invoice_date): self
    {
        $this->invoice_date = $invoice_date;

        return $this;
    }

    public function getDeliveryDate(): ?\DateTimeImmutable
    {
        return $this->delivery_date;
    }

    public function setDeliveryDate(\DateTimeImmutable $delivery_date): self
    {
        $this->delivery_date = $delivery_date;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }
}
